package com.example.badmintonshop.ui;

import android.content.Intent;
import android.os.Bundle;
import android.util.Log;
import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.widget.TextView;
import android.widget.Toast;

import androidx.annotation.NonNull;
import androidx.annotation.Nullable;
import androidx.fragment.app.Fragment;
import androidx.recyclerview.widget.LinearLayoutManager;
import androidx.recyclerview.widget.RecyclerView;

import com.example.badmintonshop.R;
import com.example.badmintonshop.adapter.OrderAdapter;
import com.example.badmintonshop.network.ApiClient;
import com.example.badmintonshop.network.ApiService;
import com.example.badmintonshop.network.dto.ApiResponse; // ⭐ Import ApiResponse
import com.example.badmintonshop.network.dto.OrderDto;
import com.example.badmintonshop.network.dto.OrderDetailDto; // ⭐ Import OrderDetailDto
import com.example.badmintonshop.network.dto.OrderListResponse;

import java.util.ArrayList;
import java.util.List;

import retrofit2.Call;
import retrofit2.Callback;
import retrofit2.Response;

public class OrderFragment extends Fragment {

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

        // Lấy Customer ID và Activity làm Listener
        YourOrdersActivity activity = (YourOrdersActivity) getActivity();
        if (activity != null) {
            customerId = activity.getCurrentCustomerId();
            orderListener = activity;
        }
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
        fetchOrders();
    }

    private void setupRecyclerView() {
        recyclerView.setLayoutManager(new LinearLayoutManager(getContext()));

        if (orderListener == null) {
            Log.e(TAG, "Order Listener is null. Cannot setup RecyclerView.");
            // Tạo listener giả để tránh lỗi NullPointer nếu Activity chưa implement
            orderListener = new OrderAdapter.OrderAdapterListener() {
                @Override public void onReviewClicked(int orderId) {}
                @Override public void onRefundClicked(int orderId) {}
                @Override public void onTrackClicked(int orderId) {}
                // Cần phải triển khai cả onBuyAgainClicked với đúng chữ ký
                @Override public void onBuyAgainClicked(int orderId) {}
            };
        }

        // Khởi tạo Adapter và truyền Listener từ Activity
        orderAdapter = new OrderAdapter(getContext(), orderList, orderListener);
        recyclerView.setAdapter(orderAdapter);
    }

    // ⭐ ĐÃ SỬA: Thay đổi từ private thành public để YourOrdersActivity có thể gọi
    public void fetchOrders() {
        if (customerId == -1 || statusFilter == null) {
            if (tvEmptyOrder != null) {
                tvEmptyOrder.setText("Vui lòng đăng nhập.");
                tvEmptyOrder.setVisibility(View.VISIBLE);
            }
            return;
        }

        String apiStatus = statusFilter.equals("All orders") ? "All" : statusFilter;
        Log.d(TAG, "Fetching orders for status: " + apiStatus + ", CustomerID: " + customerId);

        api.getCustomerOrders(customerId, apiStatus).enqueue(new Callback<OrderListResponse>() {
            @Override
            public void onResponse(Call<OrderListResponse> call, Response<OrderListResponse> response) {
                if (response.isSuccessful() && response.body() != null && response.body().isSuccess()) {
                    List<OrderDto> fetchedOrders = response.body().getOrders();

                    orderList.clear();
                    if (fetchedOrders != null) {
                        orderList.addAll(fetchedOrders);
                    }

                    orderAdapter.updateData(orderList);
                    updateUIState(false, null);
                } else {
                    String msg = response.body() != null ? response.body().getMessage() : "HTTP " + response.code();
                    Log.e(TAG, "Failed to fetch orders (" + statusFilter + "): " + msg);
                    Toast.makeText(getContext(), "Lỗi tải đơn hàng: " + msg, Toast.LENGTH_SHORT).show();
                    orderList.clear();
                    orderAdapter.updateData(orderList);
                    updateUIState(true, "Lỗi tải dữ liệu. Vui lòng thử lại.");
                }
            }

            @Override
            public void onFailure(Call<OrderListResponse> call, Throwable t) {
                String errorMsg = t.getMessage() != null ? t.getMessage() : "Lỗi kết nối không xác định.";
                Log.e(TAG, "Network error fetching orders: " + errorMsg, t);

                updateUIState(true, "Lỗi kết nối mạng: Kiểm tra internet và server.");
                Toast.makeText(getContext(), "Lỗi kết nối", Toast.LENGTH_SHORT).show();
            }
        });
    }

    // ⭐ PHƯƠNG THỨC XỬ LÝ MUA LẠI TOÀN BỘ ĐƠN HÀNG
    public void executeBuyAgain(int targetOrderId) {
        if (customerId == -1) {
            Toast.makeText(getContext(), "Vui lòng đăng nhập để mua lại.", Toast.LENGTH_SHORT).show();
            return;
        }

        OrderDto targetOrder = null;
        // 1. Tìm đơn hàng cần mua lại trong danh sách của Fragment
        for (OrderDto order : orderList) {
            if (order.getOrderID() == targetOrderId) {
                targetOrder = order;
                break;
            }
        }

        if (targetOrder == null || targetOrder.getItems() == null || targetOrder.getItems().isEmpty()) {
            Toast.makeText(getContext(), "Không tìm thấy chi tiết đơn hàng để mua lại.", Toast.LENGTH_SHORT).show();
            return;
        }

        Toast.makeText(getContext(), "Đang thêm " + targetOrder.getItems().size() + " sản phẩm vào giỏ hàng...", Toast.LENGTH_SHORT).show();

        // 2. Lặp qua tất cả chi tiết và gọi API AddToCart
        for (OrderDetailDto detail : targetOrder.getItems()) {
            final int variantID = detail.getVariantID();
            final int quantity = detail.getQuantity();

            // GỌI API THÊM VÀO GIỎ HÀNG (Sử dụng API Service của Fragment)
            api.addVariantToCart(customerId, variantID, quantity).enqueue(new Callback<ApiResponse>() {
                @Override
                public void onResponse(Call<ApiResponse> call, Response<ApiResponse> response) {
                    if (response.isSuccessful() && response.body() != null && response.body().isSuccess()) {
                        Log.d(TAG, "Added variant " + variantID + " to cart successfully.");
                        // Thông báo thành công riêng lẻ nếu cần (hoặc dùng Toast chung ở Activity)
                    } else {
                        Log.e(TAG, "Failed to add variant " + variantID + " to cart: " + response.code());
                    }
                }

                @Override
                public void onFailure(Call<ApiResponse> call, Throwable t) {
                    Log.e(TAG, "Network failure adding variant " + variantID + ": " + t.getMessage());
                }
            });
        }
    }


    private void updateUIState(boolean isError, String errorMessage) {
        boolean isEmpty = orderAdapter == null || orderAdapter.getItemCount() == 0;

        if (isEmpty) {
            tvEmptyOrder.setVisibility(View.VISIBLE);
            recyclerView.setVisibility(View.GONE);

            if (isError) {
                tvEmptyOrder.setText(errorMessage);
            } else {
                tvEmptyOrder.setText("Không có đơn hàng nào trong trạng thái này.");
            }
        } else {
            tvEmptyOrder.setVisibility(View.GONE);
            recyclerView.setVisibility(View.VISIBLE);
        }
    }

    private void updateUIState() {
        updateUIState(false, null);
    }
}