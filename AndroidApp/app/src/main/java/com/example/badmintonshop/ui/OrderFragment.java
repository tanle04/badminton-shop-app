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
import com.example.badmintonshop.network.dto.OrderDto;
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

        // Lấy Customer ID từ Activity
        YourOrdersActivity activity = (YourOrdersActivity) getActivity();
        if (activity != null) {
            customerId = activity.getCurrentCustomerId();
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

        // Thêm listener cho các hành động (Review, Refund, Track)
        OrderAdapter.OrderAdapterListener listener = new OrderAdapter.OrderAdapterListener() {
            @Override
            public void onReviewClicked(int orderId) {
                Intent intent = new Intent(getContext(), ReviewActivity.class);
                intent.putExtra("orderID", orderId);
                startActivity(intent);
            }

            @Override
            public void onRefundClicked(int orderId) {
                Toast.makeText(getContext(), "Hoàn/Đổi hàng đơn " + orderId, Toast.LENGTH_SHORT).show();
            }

            @Override
            public void onTrackClicked(int orderId) {
                Toast.makeText(getContext(), "Theo dõi đơn hàng " + orderId, Toast.LENGTH_SHORT).show();
            }
        };

        orderAdapter = new OrderAdapter(getContext(), orderList, listener);
        recyclerView.setAdapter(orderAdapter);
    }

    private void fetchOrders() {
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

                    orderAdapter.updateData(orderList); // Dùng updateData để refresh
                    updateUIState(false, null); // Thành công
                } else {
                    String msg = response.body() != null ? response.body().getMessage() : "HTTP " + response.code();
                    Log.e(TAG, "Failed to fetch orders (" + statusFilter + "): " + msg);
                    Toast.makeText(getContext(), "Lỗi tải đơn hàng: " + msg, Toast.LENGTH_SHORT).show();
                    orderList.clear();
                    orderAdapter.updateData(orderList);
                    updateUIState(true, "Lỗi tải dữ liệu. Vui lòng thử lại."); // Lỗi logic/HTTP
                }
            }

            @Override
            public void onFailure(Call<OrderListResponse> call, Throwable t) {
                String errorMsg = t.getMessage() != null ? t.getMessage() : "Lỗi kết nối không xác định.";
                Log.e(TAG, "Network error fetching orders: " + errorMsg, t);

                // ⭐ SỬA: Hiển thị lỗi kết nối trên TextView
                updateUIState(true, "Lỗi kết nối mạng: Kiểm tra internet và server.");
                Toast.makeText(getContext(), "Lỗi kết nối", Toast.LENGTH_SHORT).show();
            }
        });
    }

    private void updateUIState(boolean isError, String errorMessage) {
        boolean isEmpty = orderAdapter == null || orderAdapter.getItemCount() == 0;

        if (isEmpty) {
            tvEmptyOrder.setVisibility(View.VISIBLE);
            recyclerView.setVisibility(View.GONE);

            if (isError) {
                // Hiển thị thông báo lỗi cụ thể
                tvEmptyOrder.setText(errorMessage);
            } else {
                // Hiển thị thông báo trống mặc định
                tvEmptyOrder.setText("Không có đơn hàng nào trong trạng thái này.");
            }
        } else {
            tvEmptyOrder.setVisibility(View.GONE);
            recyclerView.setVisibility(View.VISIBLE);
        }
    }

    private void updateUIState() {
        // Quá tải phương thức để giữ nguyên khả năng gọi updateUIState() cũ
        updateUIState(false, null);
    }
}