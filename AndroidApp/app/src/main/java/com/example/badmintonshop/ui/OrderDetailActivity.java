package com.example.badmintonshop.ui;

import android.content.Context;
import android.content.Intent;
import android.content.SharedPreferences;
import android.os.Bundle;
import android.util.Log;
import android.view.View;
import android.widget.Button;
import android.widget.TextView;
import android.widget.Toast;
import android.content.ClipData; // Thêm import này
import android.content.ClipboardManager; // Thêm import này

import androidx.activity.result.ActivityResultLauncher;
import androidx.activity.result.contract.ActivityResultContracts;
import androidx.annotation.NonNull;
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
import com.example.badmintonshop.network.dto.OrderDto;
import com.example.badmintonshop.network.dto.OrderDetailDto;
import com.google.android.material.appbar.MaterialToolbar;
import com.google.gson.Gson;

// ⭐ SỬA LỖI 2: Thêm import cho ArrayList
import java.util.ArrayList;
import java.util.List;
import java.util.Locale;

import retrofit2.Call;
import retrofit2.Callback;
import retrofit2.Response;

public class OrderDetailActivity extends AppCompatActivity {

    private static final String TAG = "OrderDetailActivity";
    private static final String DEBUG_TAG = "REPAY_CLICK_DEBUG";

    private OrderDto orderDetailData;
    private ApiService apiService;
    private int currentCustomerId = -1;

    // Views
    private MaterialToolbar toolbar;
    private TextView tvPaymentMethodLabel;
    private TextView tvRecipientInfo;
    private TextView tvShippingAddress;
    private RecyclerView recyclerOrderItems;
    private TextView tvOrderTrackingCode;
    private Button btnCopyCode;
    private TextView tvFinalTotalPayment;
    private Button btnRepay;
    private Button btnCancel;
    private TextView tvSummarySubtotalAmount;
    private TextView tvShippingFeeSummary;
    private TextView tvTotalFinalSummary;
    private TextView tvVoucherDiscountSummary;
    private View voucherDetailContainer;
    private TextView tvVoucherCodeLabel;

    // Activity Result Launcher
    private final ActivityResultLauncher<Intent> paymentLauncher = registerForActivityResult(
            new ActivityResultContracts.StartActivityForResult(),
            result -> {
                if (result.getResultCode() == RESULT_OK) {
                    Log.i(TAG, "Repayment successful via WebView. Launching Success Screen.");
                    Intent intent = new Intent(this, PaymentSuccessActivity.class);
                    intent.addFlags(Intent.FLAG_ACTIVITY_CLEAR_TOP | Intent.FLAG_ACTIVITY_NEW_TASK);
                    startActivity(intent);
                    finish();
                } else {
                    Log.w(TAG, "Repayment was cancelled or failed via WebView.");
                    if (!isFinishing() && !isDestroyed()) {
                        Toast.makeText(this, "Thanh toán đã bị hủy hoặc thất bại.", Toast.LENGTH_SHORT).show();
                        // ⭐ SỬA LỖI 1: Gọi hàm overload không tham số
                        fetchOrderDetailsOverload(); // Gọi hàm overload
                    }
                }
            }
    );

    // --- Lifecycle Methods ---

    @Override
    protected void onCreate(@Nullable Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        Log.d(DEBUG_TAG, "onCreate: Activity starting."); // LOG_A
        setContentView(R.layout.activity_order_detail);

        apiService = ApiClient.getApiService();
        if (apiService == null) {
            Log.e(TAG, "ApiService is NULL in onCreate!");
            Toast.makeText(this, "Lỗi khởi tạo dịch vụ mạng.", Toast.LENGTH_LONG).show();
            finish();
            return;
        }

        currentCustomerId = getCurrentCustomerId();
        if (currentCustomerId == -1) {
            Toast.makeText(this, "Lỗi xác thực người dùng.", Toast.LENGTH_SHORT).show();
            finish();
            return;
        }

        setupViews();

        if (getIntent().hasExtra("ORDER_DETAIL_DATA")) {
            orderDetailData = (OrderDto) getIntent().getSerializableExtra("ORDER_DETAIL_DATA");
            Log.d(DEBUG_TAG, "onCreate: Received ORDER_DETAIL_DATA from Intent. Order ID: " + (orderDetailData != null ? orderDetailData.getOrderID() : "null")); // LOG_B
            if (orderDetailData != null) {
                displayDetails();
            } else {
                Toast.makeText(this, "Lỗi: Dữ liệu đơn hàng không hợp lệ.", Toast.LENGTH_SHORT).show();
                finish();
            }
        } else {
            int orderId = getIntent().getIntExtra("ORDER_ID", -1);
            Log.d(DEBUG_TAG, "onCreate: No ORDER_DETAIL_DATA in Intent. Trying to fetch ORDER_ID: " + orderId); // LOG_C
            if (orderId != -1) {
                fetchOrderDetails(orderId);
            } else {
                Toast.makeText(this, "Lỗi: Không có ID đơn hàng.", Toast.LENGTH_SHORT).show();
                finish();
            }
        }
    }

    // --- View Setup ---

    private void setupViews() {
        Log.d(DEBUG_TAG, "setupViews: Starting view initialization."); // LOG_D
        toolbar = findViewById(R.id.toolbar_order_detail);
        setSupportActionBar(toolbar);
        if (getSupportActionBar() != null) {
            getSupportActionBar().setDisplayHomeAsUpEnabled(true);
            getSupportActionBar().setTitle("Thông tin đơn hàng");
        }
        toolbar.setNavigationOnClickListener(v -> finish());

        tvPaymentMethodLabel = findViewById(R.id.tv_payment_method_label);
        tvRecipientInfo = findViewById(R.id.tv_recipient_info);
        tvShippingAddress = findViewById(R.id.tv_shipping_address);
        recyclerOrderItems = findViewById(R.id.recycler_order_items);
        tvOrderTrackingCode = findViewById(R.id.tv_order_tracking_code);
        btnCopyCode = findViewById(R.id.btn_copy_code);
        tvFinalTotalPayment = findViewById(R.id.tv_final_total_payment);

        btnRepay = findViewById(R.id.btn_repay);
        btnCancel = findViewById(R.id.btn_cancel);
        Log.d(DEBUG_TAG, "setupViews: findViewById(R.id.btn_repay) resulted in null? " + (btnRepay == null)); // LOG_E
        Log.d(DEBUG_TAG, "setupViews: findViewById(R.id.btn_cancel) resulted in null? " + (btnCancel == null)); // LOG_F

        tvSummarySubtotalAmount = findViewById(R.id.tv_summary_subtotal_amount);
        tvShippingFeeSummary = findViewById(R.id.tv_shipping_fee_summary);
        tvVoucherDiscountSummary = findViewById(R.id.tv_voucher_discount_summary);
        voucherDetailContainer = findViewById(R.id.voucher_detail_container);
        tvVoucherCodeLabel = findViewById(R.id.tv_voucher_code_label);
        tvTotalFinalSummary = findViewById(R.id.tv_total_final_summary);

        Log.d(DEBUG_TAG, "setupViews: Finished view initialization."); // LOG_G
    }

    // --- Data Loading & Display ---

    // Hàm fetch có tham số int orderId
    private void fetchOrderDetails(int orderId) {
        Log.d(DEBUG_TAG, "fetchOrderDetails(int): Fetching details for Order ID: " + orderId); // LOG_H
        if (orderId == -1 || currentCustomerId == -1) {
            Log.w(DEBUG_TAG, "fetchOrderDetails(int): Invalid orderId or customerId. Aborting fetch.");
            return;
        }

        // (Hiển thị loading indicator nếu cần)

        apiService.getOrderDetails(orderId, currentCustomerId).enqueue(new Callback<OrderDto>() {
            @Override
            public void onResponse(@NonNull Call<OrderDto> call, @NonNull Response<OrderDto> response) {
                if (isFinishing() || isDestroyed()) return;

                if (response.isSuccessful() && response.body() != null) {
                    Log.d(DEBUG_TAG, "fetchOrderDetails(int): Success. Received Order ID: " + response.body().getOrderID()); // LOG_I
                    orderDetailData = response.body();
                    displayDetails();
                } else {
                    String errorMsg = parseErrorMessage(response);
                    Log.e(DEBUG_TAG, "fetchOrderDetails(int): Failed. " + errorMsg + " (Code: " + response.code() + ")"); // LOG_J
                    Toast.makeText(OrderDetailActivity.this, "Lỗi khi tải dữ liệu: " + errorMsg, Toast.LENGTH_SHORT).show();
                    finish();
                }
                // (Ẩn loading indicator nếu có)
            }

            @Override
            public void onFailure(@NonNull Call<OrderDto> call, @NonNull Throwable t) {
                if (isFinishing() || isDestroyed()) return;
                Log.e(DEBUG_TAG, "fetchOrderDetails(int): Network Failure: " + t.getMessage(), t); // LOG_K
                Toast.makeText(OrderDetailActivity.this, "Lỗi mạng: " + t.getMessage(), Toast.LENGTH_SHORT).show();
                finish();
                // (Ẩn loading indicator nếu có)
            }
        });
    }

    // ⭐ SỬA LỖI 1: Đổi tên hàm overload để tránh nhầm lẫn
    // Hàm fetch không tham số (dùng orderId hiện có)
    private void fetchOrderDetailsOverload() {
        Log.d(DEBUG_TAG, "fetchOrderDetailsOverload: Trying to refetch using existing data.");
        if (orderDetailData != null) {
            fetchOrderDetails(orderDetailData.getOrderID()); // Gọi hàm có tham số int
        } else {
            Log.e(DEBUG_TAG, "fetchOrderDetailsOverload: Cannot refetch, orderDetailData is null.");
            // Optional: Show a toast or finish if data is unexpectedly null
            Toast.makeText(this, "Lỗi: Không có dữ liệu đơn hàng để tải lại.", Toast.LENGTH_SHORT).show();
            // finish();
        }
    }


    private void displayDetails() {
        Log.d(DEBUG_TAG, "displayDetails: Starting."); // LOG_L
        if (orderDetailData == null) {
            Log.e(TAG, "displayDetails called but orderDetailData is null! Cannot display details.");
            Toast.makeText(this, "Lỗi hiển thị chi tiết đơn hàng.", Toast.LENGTH_SHORT).show();
            return;
        }

        Log.d(DEBUG_TAG, "displayDetails: btnRepay is null before checks? " + (btnRepay == null)); // LOG_M
        Log.d(DEBUG_TAG, "displayDetails: btnCancel is null before checks? " + (btnCancel == null)); // LOG_N

        if (btnRepay == null || btnCancel == null) {
            Log.e(TAG, "Cannot setup action buttons because btnRepay or btnCancel is null! Check layout file and IDs.");
            if (!isFinishing() && !isDestroyed()) {
                Toast.makeText(this, "Lỗi giao diện nút hành động.", Toast.LENGTH_SHORT).show();
            }
            return;
        }

        // --- Lấy dữ liệu từ DTO ---
        String paymentMethod = orderDetailData.getPaymentMethod();
        double total = orderDetailData.getTotal();
        int orderId = orderDetailData.getOrderID();
        String status = orderDetailData.getStatus();
        String paymentStatus = orderDetailData.getPaymentStatus();

        // --- Log dữ liệu kiểm tra ---
        Log.d("REPAY_DEBUG", "--- Checking Order #" + orderId + " ---");
        Log.d("REPAY_DEBUG", "paymentMethod: [" + paymentMethod + "]");
        Log.d("REPAY_DEBUG", "status: [" + status + "]");
        Log.d("REPAY_DEBUG", "paymentStatus: [" + paymentStatus + "]");

        // --- Cập nhật UI (Payment, Address, Items, Summary) ---
        String paymentText;
        if ("COD".equals(paymentMethod)) {
            paymentText = "Thanh toán khi nhận hàng";
        } else if ("Paid".equals(paymentStatus)) {
            paymentText = "Đã thanh toán bằng VNPay";
        } else {
            paymentText = "Chờ thanh toán bằng VNPay";
        }
        tvPaymentMethodLabel.setText(paymentText);

        String recipient = orderDetailData.getRecipientName();
        String phone = orderDetailData.getPhone();
        String street = orderDetailData.getStreet();
        String city = orderDetailData.getCity();
        tvRecipientInfo.setText(String.format(Locale.getDefault(), "%s (+84 %s)", recipient, phone)); // Sử dụng Locale.getDefault()
        tvShippingAddress.setText(String.format(Locale.getDefault(), "%s, %s", street, city)); // Sử dụng Locale.getDefault()

        if (orderDetailData.getItems() != null && !orderDetailData.getItems().isEmpty()) {
            // Kiểm tra context hợp lệ
            if(this != null && !isFinishing() && !isDestroyed()){
                OrderDetailAdapter itemAdapter = new OrderDetailAdapter(this, orderDetailData.getItems());
                recyclerOrderItems.setLayoutManager(new LinearLayoutManager(this));
                recyclerOrderItems.setAdapter(itemAdapter);
            }
        } else {
            Log.w(TAG, "Order items list is null or empty for Order ID: " + orderId);
            // Xóa adapter cũ nếu có để hiển thị danh sách rỗng
            if (recyclerOrderItems != null && recyclerOrderItems.getAdapter() != null) {
                // ⭐ SỬA LỖI 2: Tạo ArrayList rỗng đúng cách
                ((OrderDetailAdapter)recyclerOrderItems.getAdapter()).updateData(new ArrayList<OrderDetailDto>()); // Thêm kiểu dữ liệu
            } else if (recyclerOrderItems != null) {
                // Nếu chưa có adapter, set adapter rỗng
                recyclerOrderItems.setAdapter(new OrderDetailAdapter(this, new ArrayList<OrderDetailDto>()));
                recyclerOrderItems.setLayoutManager(new LinearLayoutManager(this));
            }
        }

        double subtotalFromDto = orderDetailData.getSubtotal();
        boolean isFreeShip = orderDetailData.isFreeShip();
        double actualShippingFee = orderDetailData.getShippingFee();
        String voucherCode = orderDetailData.getVoucherCode();
        double discountAmount = orderDetailData.getDiscountAmount();
        tvSummarySubtotalAmount.setText(formatCurrency(subtotalFromDto));
        if (tvShippingFeeSummary != null) {
            tvShippingFeeSummary.setText(isFreeShip ? "0 đ" : formatCurrency(actualShippingFee));
        }
        if (voucherCode != null && !voucherCode.isEmpty() && discountAmount > 0) {
            tvVoucherCodeLabel.setText(String.format(Locale.getDefault(), "Mã giảm giá (%s):", voucherCode));
            tvVoucherDiscountSummary.setText(String.format(Locale.GERMAN, "- %,.0f đ", discountAmount));
            if (voucherDetailContainer != null) voucherDetailContainer.setVisibility(View.VISIBLE);
        } else {
            if (voucherDetailContainer != null) voucherDetailContainer.setVisibility(View.GONE);
        }
        tvTotalFinalSummary.setText(formatCurrency(total));
        tvFinalTotalPayment.setText(formatCurrency(total));
        String orderCode = "ORDER#" + orderId;
        tvOrderTrackingCode.setText(orderCode);

        if (btnCopyCode != null) {
            btnCopyCode.setOnClickListener(v -> copyToClipboard(orderCode));
        }


        // --- Cập nhật nút hành động ---
        Log.d(DEBUG_TAG, "displayDetails: Setting button visibility and listeners."); // LOG_O

        // Reset nút
        btnRepay.setVisibility(View.GONE);
        btnRepay.setOnClickListener(null);
        btnCancel.setVisibility(View.GONE);
        btnCancel.setOnClickListener(null);

        // Logic cho Nút Repay/Track
        boolean canRepay = "VNPay".equals(paymentMethod) && "Pending".equals(status) &&
                (paymentStatus == null || "".equals(paymentStatus) || "Pending".equals(paymentStatus) || "Unpaid".equals(paymentStatus) || "Failed".equals(paymentStatus));

        if (canRepay) {
            Log.d(DEBUG_TAG, "displayDetails: Condition MET for REPAY button."); // LOG_P
            btnRepay.setVisibility(View.VISIBLE);
            btnRepay.setText("Thanh toán lại");
            btnRepay.setBackgroundTintList(ContextCompat.getColorStateList(this, R.color.green_700));
            btnRepay.setOnClickListener(v -> {
                Log.d(DEBUG_TAG, "Detail: btnRepay OnClickListener called!"); // LOG_Q
                initiateRepayment(orderId);
            });
        } else if ("Shipped".equals(status)) {
            Log.d(DEBUG_TAG, "displayDetails: Condition MET for TRACK button."); // LOG_R
            btnRepay.setVisibility(View.VISIBLE);
            btnRepay.setText("Theo dõi đơn hàng");
            btnRepay.setBackgroundTintList(ContextCompat.getColorStateList(this, R.color.orange_500));
            btnRepay.setOnClickListener(v -> {
                Log.d(DEBUG_TAG, "Detail: Track Button OnClickListener called!"); // LOG_S
                Toast.makeText(this, "Chức năng theo dõi đang phát triển", Toast.LENGTH_SHORT).show();
            });
        } else {
            Log.d(DEBUG_TAG, "displayDetails: Condition NOT MET for Repay/Track button."); // LOG_T
        }

        // Logic cho Nút Cancel
        boolean canCancel = "Pending".equals(status) || "Processing".equals(status);

        if (canCancel) {
            Log.d(DEBUG_TAG, "displayDetails: Condition MET for CANCEL button."); // LOG_U
            btnCancel.setVisibility(View.VISIBLE);
            btnCancel.setText("Hủy đơn hàng");
            btnCancel.setBackgroundTintList(ContextCompat.getColorStateList(this, R.color.red_700));
            btnCancel.setOnClickListener(v -> {
                Log.d(DEBUG_TAG, "Detail: btnCancel OnClickListener called!"); // LOG_V
                showCancellationDialog(orderId);
            });
        } else {
            Log.d(DEBUG_TAG, "displayDetails: Condition NOT MET for Cancel button."); // LOG_W
        }

        Log.d(DEBUG_TAG, "displayDetails: Finished setting listeners."); // LOG_X
    }

    // --- Action Handlers ---

    private void initiateRepayment(int orderId) {
        Log.d(DEBUG_TAG, "Detail: initiateRepayment started for Order ID: " + orderId); // LOG_Y

        if (currentCustomerId <= 0) {
            Toast.makeText(this, "Lỗi xác thực người dùng.", Toast.LENGTH_LONG).show();
            return;
        }

        if (btnRepay != null) btnRepay.setEnabled(false);
        if (btnCancel != null) btnCancel.setEnabled(false);

        Toast.makeText(this, "Đang tạo lại link thanh toán...", Toast.LENGTH_SHORT).show();

        Log.d(DEBUG_TAG, "Detail: Calling apiService.repayOrder..."); // LOG_Z
        apiService.repayOrder(currentCustomerId, orderId).enqueue(new Callback<ApiResponse>() {
            @Override
            public void onResponse(@NonNull Call<ApiResponse> call, @NonNull Response<ApiResponse> response) {
                if (btnRepay != null) btnRepay.setEnabled(true);
                if (btnCancel != null) btnCancel.setEnabled(true);
                if (isFinishing() || isDestroyed()) return;

                if (response.isSuccessful() && response.body() != null && response.body().isSuccess()) {
                    if ("VNPAY_REDIRECT".equalsIgnoreCase(response.body().getMessage())) {
                        String vnpayUrl = response.body().getVnpayUrl();
                        if (vnpayUrl != null && !vnpayUrl.isEmpty()) {
                            Log.i(TAG, "Launching PaymentActivity (Repay) with URL: " + vnpayUrl);
                            Log.d(DEBUG_TAG, "Detail: Launching PaymentActivity..."); // LOG_AA
                            Intent intent = new Intent(OrderDetailActivity.this, PaymentActivity.class);
                            intent.putExtra("VNPAY_URL", vnpayUrl);
                            if (paymentLauncher != null) {
                                paymentLauncher.launch(intent);
                            } else {
                                Log.e(TAG, "paymentLauncher is null, cannot launch PaymentActivity!");
                            }
                        } else {
                            Log.e(DEBUG_TAG, "Detail: Repay API success but VNPAY URL is null or empty!"); // LOG_AB
                            Toast.makeText(OrderDetailActivity.this, "Lỗi: Không nhận được URL VNPay.", Toast.LENGTH_LONG).show();
                        }
                    } else {
                        Log.w(DEBUG_TAG, "Detail: Repay API success but message is not VNPAY_REDIRECT: " + response.body().getMessage()); // LOG_AC
                        Toast.makeText(OrderDetailActivity.this, response.body().getMessage(), Toast.LENGTH_LONG).show();
                    }
                } else {
                    String errorMsg = parseErrorMessage(response);
                    Log.e(DEBUG_TAG, "Detail: Repay API call failed: " + errorMsg + " (Code: " + response.code() + ")"); // LOG_AD
                    Toast.makeText(OrderDetailActivity.this, "Không thể thanh toán lại: " + errorMsg, Toast.LENGTH_LONG).show();
                }
            }

            @Override
            public void onFailure(@NonNull Call<ApiResponse> call, @NonNull Throwable t) {
                if (btnRepay != null) btnRepay.setEnabled(true);
                if (btnCancel != null) btnCancel.setEnabled(true);
                if (isFinishing() || isDestroyed()) return;
                Log.e(DEBUG_TAG, "Detail: Repay API network failure: " + t.getMessage(), t); // LOG_AE
                Toast.makeText(OrderDetailActivity.this, "Lỗi kết nối mạng: " + t.getMessage(), Toast.LENGTH_LONG).show();
            }
        });
    }

    private void showCancellationDialog(int orderId) {
        if (currentCustomerId <= 0) {
            Toast.makeText(this, "Lỗi xác thực: Vui lòng đăng nhập lại.", Toast.LENGTH_LONG).show();
            return;
        }
        new AlertDialog.Builder(this)
                .setTitle("Xác nhận Hủy Đơn hàng")
                .setMessage("Bạn có chắc chắn muốn hủy đơn hàng #" + orderId + "?")
                .setPositiveButton("Hủy ngay", (dialog, which) -> callCancelOrderApi(orderId))
                .setNegativeButton("Quay lại", null)
                .show();
    }

    private void callCancelOrderApi(int orderId) {
        if (apiService == null) {
            Log.e(TAG, "ApiService is null in callCancelOrderApi");
            Toast.makeText(this, "Lỗi dịch vụ mạng.", Toast.LENGTH_SHORT).show();
            return;
        }
        apiService.cancelOrder(currentCustomerId, orderId).enqueue(new Callback<ApiResponse>() {
            @Override
            public void onResponse(@NonNull Call<ApiResponse> call, @NonNull Response<ApiResponse> response) {
                if (isFinishing() || isDestroyed()) return;
                if (response.isSuccessful() && response.body() != null && response.body().isSuccess()) {
                    Toast.makeText(OrderDetailActivity.this, "Đã hủy đơn hàng #" + orderId + " thành công!", Toast.LENGTH_LONG).show();
                    setResult(RESULT_OK);
                    finish();
                } else {
                    String msg = parseErrorMessage(response);
                    Toast.makeText(OrderDetailActivity.this, "Hủy thất bại: " + msg, Toast.LENGTH_LONG).show();
                }
            }
            @Override
            public void onFailure(@NonNull Call<ApiResponse> call, @NonNull Throwable t) {
                if (isFinishing() || isDestroyed()) return;
                Toast.makeText(OrderDetailActivity.this, "Lỗi kết nối mạng khi hủy.", Toast.LENGTH_LONG).show();
            }
        });
    }

    // --- Utility Methods ---

    private int getCurrentCustomerId() {
        SharedPreferences prefs = getSharedPreferences("auth", MODE_PRIVATE);
        return prefs.getInt("customerID", -1);
    }

    private String formatCurrency(double price) {
        return String.format(Locale.GERMAN, "%,.0f đ", price);
    }

    private void copyToClipboard(String text) {
        ClipboardManager clipboard = (ClipboardManager) getSystemService(Context.CLIPBOARD_SERVICE);
        if (clipboard != null) {
            ClipData clip = ClipData.newPlainText("Order Code", text);
            clipboard.setPrimaryClip(clip);
            Toast.makeText(this, "Đã sao chép mã đơn hàng!", Toast.LENGTH_SHORT).show();
        } else {
            Toast.makeText(this, "Lỗi sao chép mã.", Toast.LENGTH_SHORT).show();
        }
    }

    private String parseErrorMessage(Response<?> response) {
        String defaultError = "Lỗi không xác định từ server (Code: " + response.code() + ")";
        if (response.errorBody() != null) {
            try {
                Gson gson = new Gson();
                String errorBodyString = response.errorBody().string(); // Read once
                ApiResponse errorResponse = gson.fromJson(errorBodyString, ApiResponse.class);
                if (errorResponse != null && errorResponse.getMessage() != null && !errorResponse.getMessage().isEmpty()) {
                    return errorResponse.getMessage();
                } else {
                    return !errorBodyString.isEmpty() ? errorBodyString : defaultError;
                }
            } catch (Exception e) {
                Log.e(TAG, "Error parsing error body", e);
                return defaultError + " (Lỗi đọc response)";
            }
        } else if (response.body() instanceof ApiResponse) {
            ApiResponse apiResponse = (ApiResponse) response.body();
            if (apiResponse != null && !apiResponse.isSuccess() && apiResponse.getMessage() != null && !apiResponse.getMessage().isEmpty()) {
                return apiResponse.getMessage();
            }
        }
        return defaultError;
    }

}