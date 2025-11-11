package com.example.badmintonshop.ui;

import android.content.Context;
import android.content.SharedPreferences;
import android.os.Bundle;
import android.util.Log;
import android.view.LayoutInflater;
import android.view.View;
import android.widget.ImageView;
import android.widget.LinearLayout;
import android.widget.ProgressBar;
import android.widget.TextView;
import android.widget.Toast;

import androidx.appcompat.app.AppCompatActivity;
import androidx.appcompat.widget.Toolbar;
import androidx.core.content.ContextCompat;
import androidx.recyclerview.widget.LinearLayoutManager;
import androidx.recyclerview.widget.RecyclerView;

import com.example.badmintonshop.R;
import com.example.badmintonshop.adapter.OrderDetailAdapter; // Dùng OrderDetailAdapter
import com.example.badmintonshop.network.ApiClient;
import com.example.badmintonshop.network.ApiService;
import com.example.badmintonshop.network.dto.OrderDto;
import com.example.badmintonshop.network.dto.OrderDetailDto;
import com.example.badmintonshop.network.dto.OrderTrackData;
import com.example.badmintonshop.network.dto.OrderTrackResponse;
import com.example.badmintonshop.network.dto.TimelineStep;

import java.util.List;

import retrofit2.Call;
import retrofit2.Callback;
import retrofit2.Response;

public class OrderTrackingActivity extends AppCompatActivity {

    private static final String TAG = "OrderTrackActivity";

    private int orderID;
    private int customerID;
    private ApiService apiService;

    private Toolbar toolbar;
    private ProgressBar progressBar;
    private TextView tvOrderStatusTitle, tvOrderStatusMessage;
    private LinearLayout layoutTimeline;
    private TextView tvShippingName, tvShippingPhone, tvShippingAddress, tvShippingMethod, tvTrackingCode, tvProductTitle;
    private RecyclerView rvProducts;
    private OrderDetailAdapter productAdapter;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_order_tracking);

        Log.d(TAG, "onCreate: Activity đang được tạo.");

        // Lấy IDs từ Intent
        orderID = getIntent().getIntExtra("ORDER_ID", -1);

        // ⭐ SỬA LỖI: Đổi "USER_PREFS" thành "auth" để khớp với các Activity khác
        SharedPreferences prefs = getSharedPreferences("auth", Context.MODE_PRIVATE);
        customerID = prefs.getInt("customerID", -1);
        // ⭐ KẾT THÚC SỬA LỖI

        Log.d(TAG, "OrderID: " + orderID + ", CustomerID: " + customerID);

        if (orderID == -1 || customerID == -1) {
            Toast.makeText(this, "Lỗi: Không có thông tin đơn hàng hoặc người dùng", Toast.LENGTH_SHORT).show();
            Log.e(TAG, "Lỗi nghiêm trọng: OrderID hoặc CustomerID không hợp lệ. Đang đóng Activity.");
            finish();
            return;
        }

        apiService = ApiClient.getRetrofitInstance().create(ApiService.class);

        initViews();
        setupToolbar();
        fetchTrackingData();
    }

    private void initViews() {
        Log.d(TAG, "initViews: Khởi tạo các view...");
        toolbar = findViewById(R.id.toolbar);
        progressBar = findViewById(R.id.progressBar);
        tvOrderStatusTitle = findViewById(R.id.tvOrderStatusTitle);
        tvOrderStatusMessage = findViewById(R.id.tvOrderStatusMessage);
        layoutTimeline = findViewById(R.id.layoutTimeline);
        tvShippingName = findViewById(R.id.tvShippingName);
        tvShippingPhone = findViewById(R.id.tvShippingPhone);
        tvShippingAddress = findViewById(R.id.tvShippingAddress);
        tvShippingMethod = findViewById(R.id.tvShippingMethod);
        tvTrackingCode = findViewById(R.id.tvTrackingCode);
        tvProductTitle = findViewById(R.id.tvProductTitle);
        rvProducts = findViewById(R.id.rvProducts);

        if (rvProducts == null) {
            Log.e(TAG, "initViews: LỖI! RecyclerView rvProducts không được tìm thấy (null).");
        } else {
            Log.d(TAG, "initViews: RecyclerView rvProducts đã được tìm thấy.");
            rvProducts.setLayoutManager(new LinearLayoutManager(this));
            rvProducts.setNestedScrollingEnabled(false);
        }
    }

    private void setupToolbar() {
        setSupportActionBar(toolbar);
        getSupportActionBar().setDisplayHomeAsUpEnabled(true);
        getSupportActionBar().setDisplayShowHomeEnabled(true);
        toolbar.setNavigationOnClickListener(v -> onBackPressed());
        getSupportActionBar().setTitle("Theo dõi đơn #" + orderID);
        Log.d(TAG, "setupToolbar: Toolbar đã được cài đặt.");
    }

    private void fetchTrackingData() {
        Log.d(TAG, "fetchTrackingData: Bắt đầu gọi API trackOrder với CustomerID = " + customerID);
        progressBar.setVisibility(View.VISIBLE);

        apiService.trackOrder(orderID, customerID).enqueue(new Callback<OrderTrackResponse>() {
            @Override
            public void onResponse(Call<OrderTrackResponse> call, Response<OrderTrackResponse> response) {
                progressBar.setVisibility(View.GONE);
                if (response.isSuccessful() && response.body() != null && response.body().isSuccess()) {
                    Log.d(TAG, "onResponse: API gọi thành công. Bắt đầu hiển thị dữ liệu.");
                    populateData(response.body().getData());
                } else {
                    String errorMsg = response.body() != null ? response.body().getMessage() : "Không thể tải dữ liệu (body null)";
                    Log.e(TAG, "onResponse: API gọi KHÔNG thành công. Message: " + errorMsg + ", Code: " + response.code());
                    Toast.makeText(OrderTrackingActivity.this, errorMsg, Toast.LENGTH_SHORT).show();
                }
            }

            @Override
            public void onFailure(Call<OrderTrackResponse> call, Throwable t) {
                progressBar.setVisibility(View.GONE);
                Log.e(TAG, "onFailure: Lỗi kết nối mạng.", t); // In ra toàn bộ lỗi
                Toast.makeText(OrderTrackingActivity.this, "Lỗi mạng: " + t.getMessage(), Toast.LENGTH_SHORT).show();
            }
        });
    }

    private void populateData(OrderTrackData data) {
        if (data == null) {
            Log.e(TAG, "populateData: Dữ liệu (OrderTrackData) bị null, không thể hiển thị.");
            return;
        }

        Log.d(TAG, "populateData: Đang hiển thị dữ liệu...");
        // 1. Cập nhật Status (dùng OrderDto)
        populateStatus(data.getOrderInfo());

        // 2. Cập nhật Timeline
        populateTimeline(data.getTimelineSteps());

        // 3. Cập nhật thông tin giao hàng (dùng OrderDto)
        populateShippingInfo(data.getOrderInfo());

        // 4. Cập nhật danh sách sản phẩm (dùng List<OrderDetailDto>)
        populateProducts(data.getProducts());
    }

    private void populateStatus(OrderDto orderInfo) {
        if (orderInfo == null) {
            Log.w(TAG, "populateStatus: orderInfo bị null.");
            return;
        }

        String status = orderInfo.getStatus();
        Log.d(TAG, "populateStatus: Cập nhật trạng thái sang: " + status);
        String statusTitle = getStatusTitle(status);

        tvOrderStatusTitle.setText(statusTitle);
        tvOrderStatusMessage.setText(getStatusDescription(status));
        tvOrderStatusTitle.setTextColor(getStatusColor(status));
    }

    // --- Các hàm Helpers để lấy text và màu theo trạng thái ---

    private String getStatusTitle(String status) {
        if (status == null) return "Không rõ";
        switch (status) {
            case "Pending": return "Chờ xác nhận";
            case "Processing": return "Đang xử lý";
            case "Shipped": return "Đang giao hàng";
            case "Delivered": return "Đã giao thành công";
            case "Cancelled": return "Đã hủy";
            case "Refunded": return "Đã hoàn tiền";
            case "Refund Requested": return "Yêu cầu hoàn tiền";
            default: return "Không rõ";
        }
    }

    private String getStatusDescription(String status) {
        if (status == null) return "Không rõ trạng thái.";
        switch (status) {
            case "Pending": return "Đơn hàng của bạn đã được tiếp nhận và đang chờ xử lý.";
            case "Processing": return "Đơn hàng đang được chuẩn bị và đóng gói.";
            case "Shipped": return "Đơn hàng đã được bàn giao cho đơn vị vận chuyển.";
            case "Delivered": return "Đơn hàng đã được giao thành công đến bạn.";
            case "Cancelled": return "Đơn hàng đã bị hủy.";
            case "Refunded": return "Đơn hàng đã được hoàn tiền.";
            case "Refund Requested": return "Yêu cầu hoàn tiền của bạn đang được xem xét.";
            default: return "Không rõ trạng thái.";
        }
    }

    private int getStatusColor(String status) {
        int colorResId;
        if (status == null) status = "Pending"; // Default

        switch (status) {
            case "Delivered":
                colorResId = R.color.success_color;
                break;
            case "Shipped":
            case "Processing":
                colorResId = R.color.pending_color;
                break;
            case "Cancelled":
            case "Refunded":
            case "Refund Requested":
                colorResId = R.color.error_color;
                break;
            default: // Pending
                colorResId = R.color.grey;
        }
        return ContextCompat.getColor(this, colorResId);
    }
    // --- Kết thúc Helpers ---


    private void populateTimeline(List<TimelineStep> steps) {
        if (steps == null || steps.isEmpty()) {
            Log.w(TAG, "populateTimeline: Danh sách 'steps' bị null hoặc rỗng.");
            return;
        }

        Log.d(TAG, "populateTimeline: Đang hiển thị " + steps.size() + " bước timeline.");
        while (layoutTimeline.getChildCount() > 1) {
            layoutTimeline.removeViewAt(1);
        }

        LayoutInflater inflater = LayoutInflater.from(this);
        for (TimelineStep step : steps) {
            View stepView = inflater.inflate(R.layout.item_timeline_step, layoutTimeline, false);

            ImageView ivIcon = stepView.findViewById(R.id.ivTimelineIcon);
            TextView tvTitle = stepView.findViewById(R.id.tvTimelineTitle);
            TextView tvTimestamp = stepView.findViewById(R.id.tvTimelineTimestamp);

            tvTitle.setText(step.getTitle());

            if (step.isCompleted()) {
                tvTimestamp.setText(step.getTimestamp() != null ? step.getTimestamp() : "Đang cập nhật...");
                tvTimestamp.setVisibility(View.VISIBLE);
                // Đặt màu cho icon
                ivIcon.setColorFilter(getStatusColor(step.getStatus()));
                tvTitle.setAlpha(1.0f);
            } else {
                tvTimestamp.setVisibility(View.GONE);
                // Đặt màu xám cho icon chưa hoàn thành
                ivIcon.setColorFilter(ContextCompat.getColor(this, R.color.grey));
                tvTitle.setAlpha(0.5f);
            }

            layoutTimeline.addView(stepView);
        }
    }

    private void populateShippingInfo(OrderDto orderInfo) {
        if (orderInfo == null) {
            Log.w(TAG, "populateShippingInfo: orderInfo bị null.");
            return;
        }
        Log.d(TAG, "populateShippingInfo: Cập nhật thông tin giao hàng.");
        tvShippingName.setText(orderInfo.getRecipientName());
        tvShippingPhone.setText(orderInfo.getPhone());
        tvShippingAddress.setText(orderInfo.getStreet() + ", " + orderInfo.getCity());

        if (orderInfo.getShippingMethod() != null) {
            tvShippingMethod.setText("Vận chuyển: " + orderInfo.getShippingMethod());
            tvShippingMethod.setVisibility(View.VISIBLE);
        } else {
            tvShippingMethod.setVisibility(View.GONE);
        }

        if (orderInfo.getTrackingCode() != null && !orderInfo.getTrackingCode().isEmpty()) {
            tvTrackingCode.setText("Mã vận đơn: " + orderInfo.getTrackingCode());
            tvTrackingCode.setVisibility(View.VISIBLE);
        } else {
            tvTrackingCode.setVisibility(View.GONE);
        }
    }

    private void populateProducts(List<OrderDetailDto> products) {
        if (products == null || products.isEmpty()) {
            Log.w(TAG, "populateProducts: Danh sách 'products' bị null hoặc rỗng. Sẽ không hiển thị sản phẩm.");
            tvProductTitle.setText("SẢN PHẨM (0)");
            return;
        }

        Log.d(TAG, "populateProducts: Tìm thấy " + products.size() + " sản phẩm. Đang cài đặt adapter.");
        tvProductTitle.setText("SẢN PHẨM (" + products.size() + ")");

        // SỬA LỖI 4: Đảo ngược tham số (Context, List)
        productAdapter = new OrderDetailAdapter(this, products);
        rvProducts.setAdapter(productAdapter);
    }
}