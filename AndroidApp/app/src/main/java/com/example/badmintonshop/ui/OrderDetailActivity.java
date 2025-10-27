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
import androidx.appcompat.app.AlertDialog;

import com.example.badmintonshop.R;
import com.example.badmintonshop.adapter.OrderDetailAdapter;
import com.example.badmintonshop.network.ApiClient;
import com.example.badmintonshop.network.ApiService;
import com.example.badmintonshop.network.dto.ApiResponse;
import com.example.badmintonshop.network.dto.OrderDetailDto;
import com.example.badmintonshop.network.dto.OrderDto;
import com.google.android.material.appbar.MaterialToolbar;

import java.util.Locale;

import retrofit2.Call;
import retrofit2.Callback;
import retrofit2.Response;

public class OrderDetailActivity extends AppCompatActivity {

    private OrderDto orderDetailData;
    private ApiService apiService;
    private int currentCustomerId = 12;

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

    // ⭐ ĐÃ XÓA HARDCODED PHÍ SHIP: private final double SHIPPING_FEE = 22200;

    // Hàm Format Tiền tệ
    private String formatCurrency(double price) {
        return String.format(Locale.GERMAN, "%,.0f đ", price);
    }

    @Override
    protected void onCreate(@Nullable Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_order_detail);

        apiService = ApiClient.getApiService();

        // 1. Nhận dữ liệu OrderDto từ Intent
        if (getIntent().hasExtra("ORDER_DETAIL_DATA")) {
            orderDetailData = (OrderDto) getIntent().getSerializableExtra("ORDER_DETAIL_DATA");

            if (orderDetailData != null) {
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
        // Lấy discountAmount và Subtotal đã tính từ Backend
        double discountAmount = orderDetailData.getDiscountAmount();
        double subtotalFromDto = orderDetailData.getSubtotal();

        // ⭐ ĐỌC PHÍ SHIP VÀ CỜ FREESHIP TỪ DTO
        double actualShippingFee = orderDetailData.getShippingFee();
        boolean isFreeShip = orderDetailData.isFreeShip();


        // 1. KHỐI THANH TOÁN
        String paymentText = (paymentMethod.equals("COD")) ?
                "Thanh toán bằng Thanh toán khi nhận hàng" :
                "Đã thanh toán bằng VNPay";

        tvPaymentMethodLabel.setText(paymentText);

        // 2. KHỐI ĐỊA CHỈ (Sử dụng dữ liệu thực tế)
        tvRecipientInfo.setText(String.format("%s (+84 %s)", recipient, phone));
        tvShippingAddress.setText(String.format("%s, %s", street, city));

        // 3. KHỐI DANH SÁCH SẢN PHẨM
        if (orderDetailData.getItems() != null && !orderDetailData.getItems().isEmpty()) {
            OrderDetailAdapter itemAdapter = new OrderDetailAdapter(this, orderDetailData.getItems());
            recyclerOrderItems.setLayoutManager(new LinearLayoutManager(this));
            recyclerOrderItems.setAdapter(itemAdapter);
        }

        // 4. KHỐI TỔNG KẾT & VOUCHER

        // 4.1. Tổng tiền hàng (Subtotal)
        tvSummarySubtotalAmount.setText(formatCurrency(subtotalFromDto));

        // 4.2. ⭐ SỬA LỖI HIỂN THỊ PHÍ SHIP DỰA TRÊN CỜ FREESHIP
        if (tvShippingFeeSummary != null) {
            if (isFreeShip) {
                // Hiển thị "0 đ" khi cờ Freeship là true
                tvShippingFeeSummary.setText("0 đ");
            } else {
                // Sử dụng phí ship thực tế từ DTO (ví dụ: 22.200 đ)
                tvShippingFeeSummary.setText(formatCurrency(actualShippingFee));
            }
        }

        // 4.3. Hiển thị Voucher
        if (voucherCode != null && !voucherCode.isEmpty() && discountAmount > 0) {
            tvVoucherCodeLabel.setText(String.format(Locale.getDefault(), "Mã giảm giá (%s):", voucherCode));
            // Hiển thị giá trị giảm giá dưới dạng số âm
            tvVoucherDiscountSummary.setText(String.format(Locale.GERMAN, "- %,.0f đ", discountAmount));
            voucherDetailContainer.setVisibility(View.VISIBLE);
        } else {
            voucherDetailContainer.setVisibility(View.GONE);
        }

        // 4.4. Tổng thanh toán cuối cùng (Khối Summary và Thanh dưới cùng)
        tvTotalFinalSummary.setText(formatCurrency(total));
        tvFinalTotalPayment.setText(formatCurrency(total));


        // 5. KHỐI MÃ ĐƠN HÀNG
        String orderCode = "ORDER#" + orderId;
        tvOrderTrackingCode.setText(orderCode);
        btnCopyCode.setOnClickListener(v -> copyToClipboard(orderCode));

        // 6. THANH DƯỚI CÙNG (Nút Hành động) ⭐ LOGIC HỦY ĐƠN HÀNG
        if (status.equals("Pending") || status.equals("Processing")) {
            // Cho phép hủy khi là Pending hoặc Processing
            btnMainAction.setText("Hủy đơn hàng");
            btnMainAction.setBackgroundTintList(ContextCompat.getColorStateList(this, R.color.red_700));
            btnMainAction.setOnClickListener(v -> showCancellationDialog(orderId));
        } else if (status.equals("Shipped")) {
            // Khi là Shipped (Đã giao cho Vận chuyển), chỉ cho phép Theo dõi
            btnMainAction.setText("Theo dõi đơn hàng");
            btnMainAction.setBackgroundTintList(ContextCompat.getColorStateList(this, R.color.orange_500));
            // Thêm logic mở trình duyệt tracking nếu có tracking code
        } else {
            // Delivered, Cancelled, Refunded, hoặc các trạng thái khác
            btnMainAction.setVisibility(View.GONE);
        }
    }

    private void copyToClipboard(String text) {
        android.content.ClipboardManager clipboard = (android.content.ClipboardManager) getSystemService(Context.CLIPBOARD_SERVICE);
        android.content.ClipData clip = android.content.ClipData.newPlainText("Order Code", text);
        clipboard.setPrimaryClip(clip);
        Toast.makeText(this, "Đã sao chép mã đơn hàng!", Toast.LENGTH_SHORT).show();
    }

    private void showCancellationDialog(int orderId) {
        if (currentCustomerId <= 0) {
            Toast.makeText(this, "Lỗi xác thực: Vui lòng đăng nhập lại.", Toast.LENGTH_LONG).show();
            return;
        }

        new AlertDialog.Builder(this)
                .setTitle("Xác nhận Hủy Đơn hàng")
                .setMessage("Bạn có chắc chắn muốn hủy đơn hàng #" + orderId + "? Tồn kho sản phẩm sẽ được phục hồi.")
                .setPositiveButton("Hủy ngay", (dialog, which) -> {
                    callCancelOrderApi(orderId);
                })
                .setNegativeButton("Quay lại", null)
                .show();
    }

    private void callCancelOrderApi(int orderId) {

        apiService.cancelOrder(currentCustomerId, orderId).enqueue(new Callback<ApiResponse>() {
            @Override
            public void onResponse(Call<ApiResponse> call, Response<ApiResponse> response) {
                if (response.isSuccessful() && response.body() != null && response.body().isSuccess()) {
                    Toast.makeText(OrderDetailActivity.this, "Đã hủy đơn hàng #" + orderId + " thành công!", Toast.LENGTH_LONG).show();
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