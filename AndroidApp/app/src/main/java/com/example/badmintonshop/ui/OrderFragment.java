// File: app/src/main/java/com/example/badmintonshop/ui/OrderFragment.java
// KHÔNG CẦN SỬA FILE NÀY

package com.example.badmintonshop.ui;

import android.content.Context;
import android.content.Intent;
import android.content.SharedPreferences;
import android.os.Bundle;
import android.util.Log;
import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.widget.TextView;
import android.widget.Toast;

import androidx.activity.result.ActivityResultLauncher;
import androidx.activity.result.contract.ActivityResultContracts;
import androidx.annotation.NonNull;
import androidx.annotation.Nullable;
import androidx.appcompat.app.AppCompatActivity;
import androidx.fragment.app.Fragment;
import androidx.recyclerview.widget.LinearLayoutManager;
import androidx.recyclerview.widget.RecyclerView;

import com.example.badmintonshop.R;
import com.example.badmintonshop.adapter.OrderAdapter;
import com.example.badmintonshop.network.ApiClient;
import com.example.badmintonshop.network.ApiService;
import com.example.badmintonshop.network.dto.ApiResponse;
import com.example.badmintonshop.network.dto.OrderDto;
import com.example.badmintonshop.network.dto.OrderDetailDto;
import com.example.badmintonshop.network.dto.OrderListResponse;
import com.google.gson.Gson;

import java.util.ArrayList;
import java.util.List;

import retrofit2.Call;
import retrofit2.Callback;
import retrofit2.Response;

public class OrderFragment extends Fragment implements OrderAdapter.OrderAdapterListener {

    private static final String ARG_STATUS = "status_filter";
    private static final String TAG = "OrderFragmentDebug";

    private String statusFilter;
    private RecyclerView recyclerView;
    private TextView tvEmptyOrder;
    private OrderAdapter orderAdapter;
    private final List<OrderDto> orderList = new ArrayList<>();
    private ApiService api;
    private int customerId;

    private OrderAdapter.OrderAdapterListener orderListener;
    private ActivityResultLauncher<Intent> paymentLauncher;
    private ActivityResultLauncher<Intent> failureLauncher;

    public OrderFragment() {
        // Required empty public constructor
    }

    public static OrderFragment newInstance(String statusFilter) {
        OrderFragment fragment = new OrderFragment();
        Bundle args = new Bundle();
        args.putString(ARG_STATUS, statusFilter);
        fragment.setArguments(args);
        return fragment;
    }

    @Override
    public void onCreate(@Nullable Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        api = ApiClient.getApiService();

        if (getArguments() != null) {
            statusFilter = getArguments().getString(ARG_STATUS);
        }

        // Lấy Customer ID và Activity listener
        if (getActivity() instanceof OrderAdapter.OrderAdapterListener) {
            orderListener = (OrderAdapter.OrderAdapterListener) getActivity();
            if (getActivity() instanceof YourOrdersActivity) {
                customerId = ((YourOrdersActivity) getActivity()).getCurrentCustomerId();
            } else {
                Log.e(TAG, "Activity is not YourOrdersActivity, cannot get customer ID directly.");
                customerId = getCurrentCustomerIdFallback();
            }
        } else {
            Log.e(TAG, "Activity must implement OrderAdapter.OrderAdapterListener");
            customerId = -1;
            orderListener = createDummyListener();
        }

        // ⭐ LAUNCHER CHO MÀN HÌNH THẤT BẠI (Có nút "Thử lại")
        failureLauncher = registerForActivityResult(
                new ActivityResultContracts.StartActivityForResult(),
                result -> {
                    if (result.getResultCode() == AppCompatActivity.RESULT_OK) {
                        // User bấm "THỬ LẠI" từ PaymentFailedActivity
                        int retryOrderId = result.getData() != null ?
                                result.getData().getIntExtra("RETRY_ORDER_ID", -1) :
                                -1;

                        if (retryOrderId != -1) {
                            Log.i(TAG, "Retry requested for order ID: " + retryOrderId);
                            // ⭐ GỌI LẠI REPAYMENT
                            initiateRepayment(retryOrderId);
                        }
                    } else {
                        // User bấm "Quay về trang chủ" hoặc Back
                        Log.d(TAG, "Payment failure dismissed without retry.");
                        // ⭐ VẪN RELOAD ĐỂ CẬP NHẬT TRẠNG THÁI (ĐƠN CÓ THỂ ĐÃ BỊ HỦY)
                        fetchOrders();
                    }
                }
        );

        // ⭐ LAUNCHER CHO PAYMENT ACTIVITY (WebView VNPay)
        paymentLauncher = registerForActivityResult(
                new ActivityResultContracts.StartActivityForResult(),
                result -> {
                    // Lấy OrderID từ Intent
                    String orderIdString = result.getData() != null ?
                            result.getData().getStringExtra("ORDER_ID") :
                            null;
                    int completedOrderId = (orderIdString != null) ? Integer.parseInt(orderIdString) : -1;

                    Log.d(TAG, "Payment result received. ResultCode: " + result.getResultCode() + ", OrderID: " + completedOrderId);

                    // ⭐ LUÔN RELOAD ORDERS SAU KHI PAYMENT ACTIVITY ĐÓNG (Dù thành công hay thất bại)
                    fetchOrders();

                    if (result.getResultCode() == AppCompatActivity.RESULT_OK) {
                        // ✅ THANH TOÁN THÀNH CÔNG
                        Log.i(TAG, "Payment successful for OrderID: " + completedOrderId);

                        if (isAdded() && getContext() != null) {
                            Toast.makeText(getContext(), "Thanh toán thành công!", Toast.LENGTH_SHORT).show();

                            // Chuyển sang màn hình Success
                            Intent successIntent = new Intent(getContext(), PaymentSuccessActivity.class);
                            successIntent.putExtra("ORDER_ID", completedOrderId);
                            startActivity(successIntent);
                        }
                    } else {
                        // ❌ THANH TOÁN THẤT BẠI
                        Log.w(TAG, "Payment failed/cancelled for OrderID: " + completedOrderId);

                        if (isAdded() && getContext() != null && completedOrderId != -1) {
                            // Mở màn hình thất bại với nút "Thử lại"
                            Intent failureIntent = new Intent(getContext(), PaymentFailedActivity.class);
                            failureIntent.putExtra("ORDER_ID", completedOrderId);
                            failureLauncher.launch(failureIntent); // Dùng failureLauncher để nhận callback
                        } else {
                            if (isAdded() && getContext() != null) {
                                Toast.makeText(getContext(), "Thanh toán thất bại.", Toast.LENGTH_SHORT).show();
                            }
                        }
                    }
                }
        );
    }

    @Override
    public View onCreateView(@NonNull LayoutInflater inflater, ViewGroup container,
                             Bundle savedInstanceState) {
        View view = inflater.inflate(R.layout.fragment_order_list, container, false);
        recyclerView = view.findViewById(R.id.recycler_order_list);
        tvEmptyOrder = view.findViewById(R.id.tv_empty_order);
        setupRecyclerView();
        return view;
    }

    @Override
    public void onViewCreated(@NonNull View view, @Nullable Bundle savedInstanceState) {
        super.onViewCreated(view, savedInstanceState);
        // ⭐ GỌI fetchOrders() LẦN ĐẦU KHI FRAGMENT ĐƯỢC TẠO
        fetchOrders();
    }

    private void setupRecyclerView() {
        if (getContext() == null) {
            Log.e(TAG, "Context is null in setupRecyclerView");
            return;
        }
        recyclerView.setLayoutManager(new LinearLayoutManager(getContext()));
        orderAdapter = new OrderAdapter(getContext(), orderList, this);
        recyclerView.setAdapter(orderAdapter);
    }

    // ⭐ HÀM FETCH ORDERS - ĐƯỢC GỌI NHIỀU LẦN ĐỂ RELOAD
    public void fetchOrders() {
        if (customerId == -1 || statusFilter == null) {
            Log.w(TAG, "Cannot fetch orders: customerId=" + customerId + ", statusFilter=" + statusFilter);
            if (tvEmptyOrder != null) {
                tvEmptyOrder.setText(customerId == -1 ? "Vui lòng đăng nhập." : "Lỗi bộ lọc trạng thái.");
                tvEmptyOrder.setVisibility(View.VISIBLE);
                if (recyclerView != null) recyclerView.setVisibility(View.GONE);
            }
            return;
        }

        if (api == null) {
            api = ApiClient.getApiService();
            if (api == null) {
                Log.e(TAG, "ApiService is null, cannot fetch orders.");
                if (isAdded() && getContext() != null)
                    Toast.makeText(getContext(), "Lỗi khởi tạo dịch vụ mạng.", Toast.LENGTH_SHORT).show();
                updateUIState(true, "Lỗi dịch vụ mạng.");
                return;
            }
        }

        String apiStatus = statusFilter.equals("All orders") ? "All" : statusFilter;
        Log.d(TAG, "📡 Fetching orders for status: [" + apiStatus + "], CustomerID: [" + customerId + "]");

        api.getCustomerOrders(customerId, apiStatus).enqueue(new Callback<OrderListResponse>() {
            @Override
            public void onResponse(@NonNull Call<OrderListResponse> call, @NonNull Response<OrderListResponse> response) {
                if (!isAdded() || getContext() == null) return;

                if (response.isSuccessful() && response.body() != null && response.body().isSuccess()) {
                    List<OrderDto> fetchedOrders = response.body().getOrders();
                    Log.d(TAG, "✅ Successfully fetched " + (fetchedOrders != null ? fetchedOrders.size() : 0) + " orders for status: " + apiStatus);
                    orderList.clear();
                    if (fetchedOrders != null) {
                        orderList.addAll(fetchedOrders);
                    }
                    orderAdapter.updateData(orderList);
                    updateUIState(false, null);
                } else {
                    String msg = parseErrorMessage(response);
                    Log.e(TAG, "❌ Failed to fetch orders (" + statusFilter + "): " + msg + " (Code: " + response.code() + ")");
                    Toast.makeText(getContext(), "Lỗi tải đơn hàng: " + msg, Toast.LENGTH_SHORT).show();
                    orderList.clear();
                    orderAdapter.updateData(orderList);
                    updateUIState(true, "Lỗi tải dữ liệu. Vui lòng thử lại.");
                }
            }

            @Override
            public void onFailure(@NonNull Call<OrderListResponse> call, @NonNull Throwable t) {
                if (!isAdded() || getContext() == null) return;

                String errorMsg = t.getMessage() != null ? t.getMessage() : "Lỗi kết nối không xác định.";
                Log.e(TAG, "🔴 Network error fetching orders: " + errorMsg, t);
                updateUIState(true, "Lỗi kết nối mạng.");
                Toast.makeText(getContext(), "Lỗi kết nối mạng", Toast.LENGTH_SHORT).show();
            }
        });
    }

    public void executeBuyAgain(int targetOrderId) {
        if (customerId == -1) {
            if (isAdded() && getContext() != null)
                Toast.makeText(getContext(), "Vui lòng đăng nhập để mua lại.", Toast.LENGTH_SHORT).show();
            return;
        }

        OrderDto targetOrder = null;
        for (OrderDto order : orderList) {
            if (order.getOrderID() == targetOrderId) {
                targetOrder = order;
                break;
            }
        }

        if (targetOrder == null || targetOrder.getItems() == null || targetOrder.getItems().isEmpty()) {
            if (isAdded() && getContext() != null)
                Toast.makeText(getContext(), "Không tìm thấy chi tiết đơn hàng để mua lại.", Toast.LENGTH_SHORT).show();
            return;
        }

        if (isAdded() && getContext() != null)
            Toast.makeText(getContext(), "Đang thêm " + targetOrder.getItems().size() + " sản phẩm vào giỏ hàng...", Toast.LENGTH_SHORT).show();

        for (OrderDetailDto detail : targetOrder.getItems()) {
            final int variantID = detail.getVariantID();
            final int quantity = detail.getQuantity();

            if (api == null) {
                Log.e(TAG, "ApiService is null in executeBuyAgain loop.");
                continue;
            }

            api.addVariantToCart(customerId, variantID, quantity).enqueue(new Callback<ApiResponse>() {
                @Override
                public void onResponse(@NonNull Call<ApiResponse> call, @NonNull Response<ApiResponse> response) {
                    if (response.isSuccessful() && response.body() != null && response.body().isSuccess()) {
                        Log.d(TAG, "Added variant " + variantID + " to cart successfully.");
                    } else {
                        String error = parseErrorMessage(response);
                        Log.e(TAG, "Failed to add variant " + variantID + " to cart: " + error);
                    }
                }

                @Override
                public void onFailure(@NonNull Call<ApiResponse> call, @NonNull Throwable t) {
                    Log.e(TAG, "Network failure adding variant " + variantID + ": " + t.getMessage());
                }
            });
        }
    }

    private void updateUIState(boolean isError, String errorMessage) {
        if (tvEmptyOrder == null || recyclerView == null) {
            Log.w(TAG, "Views not initialized in updateUIState");
            return;
        }
        boolean isEmpty = orderList.isEmpty();

        if (isEmpty) {
            tvEmptyOrder.setVisibility(View.VISIBLE);
            recyclerView.setVisibility(View.GONE);
            tvEmptyOrder.setText(isError ? errorMessage : "Không có đơn hàng nào.");
        } else {
            tvEmptyOrder.setVisibility(View.GONE);
            recyclerView.setVisibility(View.VISIBLE);
        }
    }

    // --- IMPLEMENT OrderAdapter.OrderAdapterListener ---

    @Override
    public void onListRepayClicked(int orderId) {
        Log.d(TAG, "List Repay clicked for order ID: " + orderId + ". Calling initiateRepayment...");
        initiateRepayment(orderId);
    }

    @Override
    public void onReviewClicked(int orderId) {
        if (orderListener != null && orderListener != this) {
            orderListener.onReviewClicked(orderId);
        } else Log.e(TAG, "Listener null, cannot delegate onReviewClicked");
    }

    @Override
    public void onRefundClicked(int orderId) {
        if (orderListener != null && orderListener != this) orderListener.onRefundClicked(orderId);
        else Log.e(TAG, "Listener null, cannot delegate onRefundClicked");
    }

    @Override
    public void onTrackClicked(int orderId) {
        if (orderListener != null && orderListener != this) orderListener.onTrackClicked(orderId);
        else Log.e(TAG, "Listener null, cannot delegate onTrackClicked");
    }

    @Override
    public void onBuyAgainClicked(int orderId) {
        if (orderListener != null && orderListener != this) orderListener.onBuyAgainClicked(orderId);
        else Log.e(TAG, "Listener null, cannot delegate onBuyAgainClicked");
    }

    @Override
    public void onOrderClicked(OrderDto order) {
        if (orderListener != null && orderListener != this) orderListener.onOrderClicked(order);
        else Log.e(TAG, "Listener null, cannot delegate onOrderClicked");
    }

    // ⭐ HÀM INITIATE REPAYMENT - PUBLIC ĐỂ ĐƯỢC GỌI TỪ BÊN NGOÀI
    public void initiateRepayment(int orderId) {
        if (customerId <= 0) {
            if (isAdded() && getContext() != null)
                Toast.makeText(getContext(), "Lỗi xác thực người dùng.", Toast.LENGTH_LONG).show();
            return;
        }

        if (api == null) {
            Log.e(TAG, "ApiService is null in initiateRepayment.");
            if (isAdded() && getContext() != null)
                Toast.makeText(getContext(), "Lỗi dịch vụ mạng.", Toast.LENGTH_SHORT).show();
            return;
        }

        if (paymentLauncher == null) {
            Log.e(TAG, "paymentLauncher is null in initiateRepayment.");
            if (isAdded() && getContext() != null)
                Toast.makeText(getContext(), "Lỗi khởi tạo trình thanh toán.", Toast.LENGTH_SHORT).show();
            return;
        }

        if (isAdded() && getContext() != null)
            Toast.makeText(getContext(), "Đang tạo lại link thanh toán...", Toast.LENGTH_SHORT).show();

        Log.d(TAG, "🔄 Calling Repay API for OrderID: " + orderId);

        api.repayOrder(customerId, orderId).enqueue(new Callback<ApiResponse>() {
            @Override
            public void onResponse(@NonNull Call<ApiResponse> call, @NonNull Response<ApiResponse> response) {
                if (!isAdded() || getContext() == null) return;

                if (response.isSuccessful() && response.body() != null && response.body().isSuccess()) {
                    if ("VNPAY_REDIRECT".equalsIgnoreCase(response.body().getMessage())) {
                        String vnpayUrl = response.body().getVnpayUrl();
                        if (vnpayUrl != null && !vnpayUrl.isEmpty()) {
                            Log.i(TAG, "✅ Launching PaymentActivity (Repay) with URL");
                            Intent intent = new Intent(getContext(), PaymentActivity.class);
                            intent.putExtra("VNPAY_URL", vnpayUrl);
                            intent.putExtra("ORDER_ID_RET", orderId + "");
                            paymentLauncher.launch(intent);
                        } else {
                            Toast.makeText(getContext(), "Lỗi: Không nhận được URL VNPay.", Toast.LENGTH_LONG).show();
                        }
                    } else {
                        Toast.makeText(getContext(), response.body().getMessage(), Toast.LENGTH_LONG).show();
                    }
                } else {
                    String errorMsg = parseErrorMessage(response);
                    Toast.makeText(getContext(), "Không thể thanh toán lại: " + errorMsg, Toast.LENGTH_LONG).show();
                    Log.e(TAG, "❌ Repay API failed: " + errorMsg + " (Code: " + response.code() + ")");
                }
            }

            @Override
            public void onFailure(@NonNull Call<ApiResponse> call, @NonNull Throwable t) {
                if (!isAdded() || getContext() == null) return;
                Toast.makeText(getContext(), "Lỗi kết nối mạng: " + t.getMessage(), Toast.LENGTH_LONG).show();
                Log.e(TAG, "🔴 Repay network failure: ", t);
            }
        });
    }

    private int getCurrentCustomerIdFallback() {
        if (getActivity() == null) return -1;
        SharedPreferences prefs = getActivity().getSharedPreferences("auth", Context.MODE_PRIVATE);
        return prefs.getInt("customerID", -1);
    }

    private String parseErrorMessage(Response<?> response) {
        String defaultError = "Lỗi không xác định (Code: " + response.code() + ")";
        if (response.errorBody() != null) {
            try {
                Gson gson = new Gson();
                ApiResponse errorResponse = gson.fromJson(response.errorBody().string(), ApiResponse.class);
                if (errorResponse != null && errorResponse.getMessage() != null && !errorResponse.getMessage().isEmpty()) {
                    return errorResponse.getMessage();
                }
            } catch (Exception e) {
                Log.e(TAG, "Error parsing error body", e);
            }
        } else if (response.body() instanceof ApiResponse) {
            ApiResponse apiResponse = (ApiResponse) response.body();
            if (apiResponse != null && !apiResponse.isSuccess() && apiResponse.getMessage() != null && !apiResponse.getMessage().isEmpty()) {
                return apiResponse.getMessage();
            }
        }
        return defaultError;
    }

    private OrderAdapter.OrderAdapterListener createDummyListener() {
        return new OrderAdapter.OrderAdapterListener() {
            @Override
            public void onReviewClicked(int orderId) {
                Log.e(TAG, "Dummy listener called");
            }

            @Override
            public void onRefundClicked(int orderId) {
                Log.e(TAG, "Dummy listener called");
            }

            @Override
            public void onTrackClicked(int orderId) {
                Log.e(TAG, "Dummy listener called");
            }

            @Override
            public void onBuyAgainClicked(int orderId) {
                Log.e(TAG, "Dummy listener called");
            }

            @Override
            public void onOrderClicked(OrderDto order) {
                Log.e(TAG, "Dummy listener called");
            }

            @Override
            public void onListRepayClicked(int orderId) {
                Log.e(TAG, "Dummy listener called");
            }
        };
    }
}