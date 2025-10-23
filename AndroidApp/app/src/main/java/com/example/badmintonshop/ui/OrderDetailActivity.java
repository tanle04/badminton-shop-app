package com.example.badmintonshop.ui;

import android.content.Context;
import android.content.Intent;
import android.os.Bundle;
import android.view.View;
import android.widget.Button;
import android.widget.TextView;
import android.widget.Toast;

import androidx.annotation.Nullable;
import androidx.appcompat.app.AppCompatActivity;
import androidx.core.content.ContextCompat;
import androidx.recyclerview.widget.LinearLayoutManager;
import androidx.recyclerview.widget.RecyclerView;

import com.example.badmintonshop.R;
import com.example.badmintonshop.adapter.OrderDetailAdapter;
import com.example.badmintonshop.network.ApiClient;
import com.example.badmintonshop.network.ApiService; // ⭐ ĐÃ THÊM: Cần cho API hủy
import com.example.badmintonshop.network.dto.ApiResponse; // ⭐ ĐÃ THÊM: Cần cho API hủy
import com.example.badmintonshop.network.dto.OrderDetailDto;
import com.example.badmintonshop.network.dto.OrderDto;
import com.google.android.material.appbar.MaterialToolbar;

import java.util.Locale;

import androidx.appcompat.app.AlertDialog; // ⭐ ĐÃ THÊM: Cần cho AlertDialog
import retrofit2.Call;
import retrofit2.Callback;
import retrofit2.Response;

public class OrderDetailActivity extends AppCompatActivity {

    private OrderDto orderDetailData;
    private ApiService apiService; // ⭐ ĐÃ THÊM: Khai báo ApiService
    private int currentCustomerId = 12; // ⭐ Giả định lấy CustomerID (bạn cần thay bằng logic thực)

    // Khai báo các Views chính
    private MaterialToolbar toolbar;
    private TextView tvPaymentMethodLabel;
    private TextView tvRecipientInfo;
    private TextView tvShippingAddress;
    private RecyclerView recyclerOrderItems;
    private TextView tvOrderTrackingCode;
    private Button btnCopyCode;

    // Khối Actions (Thanh dưới cùng)
    private TextView tvFinalTotalPayment;
    private Button btnMainAction;

    // Khối Summary (4. Tổng kết)
    private TextView tvSummarySubtotalAmount;
    private TextView tvShippingFeeSummary;
    private TextView tvTotalFinalSummary;
    private TextView tvVoucherDiscountSummary;
    private View voucherDetailContainer;
    private TextView tvVoucherCodeLabel;

    // Hardcoded phí ship (Cần thiết cho tổng kết)
    private final double SHIPPING_FEE = 22200;


    @Override
    protected void onCreate(@Nullable Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_order_detail);

        apiService = ApiClient.getApiService(); // ⭐ KHỞI TẠO API SERVICE

        // 1. Nhận dữ liệu OrderDto từ Intent
        if (getIntent().hasExtra("ORDER_DETAIL_DATA")) {
            orderDetailData = (OrderDto) getIntent().getSerializableExtra("ORDER_DETAIL_DATA");

            if (orderDetailData != null) {
                // ⭐ Lấy CustomerID thực tế từ SharedPreferences nếu cần
                // currentCustomerId = getSharedPreferences("auth", MODE_PRIVATE).getInt("customerID", -1);

                setupViews();
                displayDetails();
            } else {
                Toast.makeText(this, "Không tìm thấy chi tiết đơn hàng.", Toast.LENGTH_SHORT).show();
                finish();
            }
        } else {
            Toast.makeText(this, "Lỗi: Không có dữ liệu truyền vào.", Toast.LENGTH_SHORT).show();
            finish();
        }
    }

    private void setupViews() {
        // Ánh xạ Toolbar
        toolbar = findViewById(R.id.toolbar_order_detail);
        setSupportActionBar(toolbar);
        if (getSupportActionBar() != null) {
            getSupportActionBar().setDisplayHomeAsUpEnabled(true);
            getSupportActionBar().setTitle("Thông tin đơn hàng");
        }
        toolbar.setNavigationOnClickListener(v -> finish());

        // Ánh xạ các thành phần chính
        tvPaymentMethodLabel = findViewById(R.id.tv_payment_method_label);
        tvRecipientInfo = findViewById(R.id.tv_recipient_info);
        tvShippingAddress = findViewById(R.id.tv_shipping_address);
        recyclerOrderItems = findViewById(R.id.recycler_order_items);
        tvOrderTrackingCode = findViewById(R.id.tv_order_tracking_code);
        btnCopyCode = findViewById(R.id.btn_copy_code);
        tvFinalTotalPayment = findViewById(R.id.tv_final_total_payment);
        btnMainAction = findViewById(R.id.btn_main_action);

        // Ánh xạ Views Khối Tổng kết
        tvSummarySubtotalAmount = findViewById(R.id.tv_summary_subtotal_amount);
        tvShippingFeeSummary = findViewById(R.id.tv_shipping_fee_summary);
        tvVoucherDiscountSummary = findViewById(R.id.tv_voucher_discount_summary);
        voucherDetailContainer = findViewById(R.id.voucher_detail_container);
        tvVoucherCodeLabel = findViewById(R.id.tv_voucher_code_label);
        tvTotalFinalSummary = findViewById(R.id.tv_total_final_summary);
    }

    private void displayDetails() {
        // Lấy dữ liệu từ DTO
        String paymentMethod = orderDetailData.getPaymentMethod();
        double total = orderDetailData.getTotal();
        int orderId = orderDetailData.getOrderID();
        String status = orderDetailData.getStatus();

        String recipient = orderDetailData.getRecipientName();
        String phone = orderDetailData.getPhone();
        String street = orderDetailData.getStreet();
        String city = orderDetailData.getCity();

        String voucherCode = orderDetailData.getVoucherCode();
        double discountAmount = orderDetailData.getDiscountAmount();
        double shippingFee = SHIPPING_FEE;


        // 1. KHỐI THANH TOÁN
        String paymentText = (paymentMethod.equals("COD")) ?
                "Thanh toán bằng Thanh toán khi nhận hàng" :
                "Đã thanh toán bằng VNPay";

        tvPaymentMethodLabel.setText(paymentText);

        // 2. KHỐI ĐỊA CHỈ (Sử dụng dữ liệu thực tế)
        tvRecipientInfo.setText(String.format("%s (+84 %s)", recipient, phone));
        tvShippingAddress.setText(String.format("%s, %s", street, city));

        // 3. KHỐI DANH SÁCH SẢN PHẨM & TÍNH SUBTOTAL
        double subtotal = 0;
        if (orderDetailData.getItems() != null && !orderDetailData.getItems().isEmpty()) {
            for (OrderDetailDto item : orderDetailData.getItems()) {
                subtotal += item.getPrice() * item.getQuantity();
            }

            OrderDetailAdapter itemAdapter = new OrderDetailAdapter(this, orderDetailData.getItems());
            recyclerOrderItems.setLayoutManager(new LinearLayoutManager(this));
            recyclerOrderItems.setAdapter(itemAdapter);
        }

        // 4. KHỐI TỔNG KẾT & VOUCHER

        // 4.1. Tổng tiền hàng (Subtotal)
        tvSummarySubtotalAmount.setText(String.format(Locale.GERMAN, "%,.0f đ", subtotal));

        // 4.2. Hiển thị Voucher
        if (voucherCode != null && !voucherCode.isEmpty() && discountAmount > 0) {
            tvVoucherCodeLabel.setText(String.format(Locale.getDefault(), "Mã giảm giá (%s):", voucherCode));
            tvVoucherDiscountSummary.setText(String.format(Locale.GERMAN, "- %,.0f đ", discountAmount));
            voucherDetailContainer.setVisibility(View.VISIBLE);
        } else {
            voucherDetailContainer.setVisibility(View.GONE);
        }

        // 4.3. Hiển thị Phí ship
        if (tvShippingFeeSummary != null) {
            tvShippingFeeSummary.setText(String.format(Locale.GERMAN, "%,.0f đ", shippingFee));
        }

        // 4.4. Tổng thanh toán cuối cùng (Khối Summary và Thanh dưới cùng)
        tvTotalFinalSummary.setText(String.format(Locale.GERMAN, "%,.0f đ", total));
        tvFinalTotalPayment.setText(String.format(Locale.GERMAN, "%,.0f đ", total));


        // 5. KHỐI MÃ ĐƠN HÀNG
        String orderCode = "ORDER#" + orderId;
        tvOrderTrackingCode.setText(orderCode);
        btnCopyCode.setOnClickListener(v -> copyToClipboard(orderCode));

        // 6. THANH DƯỚI CÙNG (Nút Hành động)
        if (status.equals("Pending")) {
            btnMainAction.setText("Hủy đơn hàng");
            btnMainAction.setBackgroundTintList(ContextCompat.getColorStateList(this, R.color.red_700));
            // ⭐ ĐÃ SỬA: Thay thế Toast bằng AlertDialog
            btnMainAction.setOnClickListener(v -> showCancellationDialog(orderId));
        } else if (status.equals("Processing") || status.equals("Shipped")) {
            btnMainAction.setText("Theo dõi đơn hàng");
            btnMainAction.setBackgroundTintList(ContextCompat.getColorStateList(this, R.color.orange_500));
        } else {
            btnMainAction.setVisibility(View.GONE);
        }
    }

    private void copyToClipboard(String text) {
        android.content.ClipboardManager clipboard = (android.content.ClipboardManager) getSystemService(Context.CLIPBOARD_SERVICE);
        android.content.ClipData clip = android.content.ClipData.newPlainText("Order Code", text);
        clipboard.setPrimaryClip(clip);
        Toast.makeText(this, "Đã sao chép mã đơn hàng!", Toast.LENGTH_SHORT).show();
    }

    // ⭐ ĐÃ SỬA: Phương thức hiển thị AlertDialog và gọi API hủy
    private void showCancellationDialog(int orderId) {
        if (currentCustomerId <= 0) {
            Toast.makeText(this, "Lỗi xác thực: Vui lòng đăng nhập lại.", Toast.LENGTH_LONG).show();
            return;
        }

        new AlertDialog.Builder(this)
                .setTitle("Xác nhận Hủy Đơn hàng")
                .setMessage("Bạn có chắc chắn muốn hủy đơn hàng #" + orderId + "? Tồn kho sản phẩm sẽ được phục hồi.")
                .setPositiveButton("Hủy ngay", (dialog, which) -> {
                    callCancelOrderApi(orderId); // GỌI API HỦY
                })
                .setNegativeButton("Quay lại", null)
                .show();
    }

    // ⭐ ĐÃ THÊM: HÀM GỌI API HỦY
    private void callCancelOrderApi(int orderId) {

        apiService.cancelOrder(currentCustomerId, orderId).enqueue(new Callback<ApiResponse>() {
            @Override
            public void onResponse(Call<ApiResponse> call, Response<ApiResponse> response) {
                if (response.isSuccessful() && response.body() != null && response.body().isSuccess()) {
                    Toast.makeText(OrderDetailActivity.this, "Đã hủy đơn hàng #" + orderId + " thành công!", Toast.LENGTH_LONG).show();
                    // Trả về RESULT_OK cho Activity cha (YourOrdersActivity) để refresh
                    setResult(RESULT_OK);
                    finish();
                } else {
                    String msg = response.body() != null ? response.body().getMessage() : "Lỗi server (" + response.code() + ")";
                    Toast.makeText(OrderDetailActivity.this, "Hủy thất bại: " + msg, Toast.LENGTH_LONG).show();
                }
            }

            @Override
            public void onFailure(Call<ApiResponse> call, Throwable t) {
                Toast.makeText(OrderDetailActivity.this, "Lỗi kết nối mạng.", Toast.LENGTH_LONG).show();
            }
        });
    }
}