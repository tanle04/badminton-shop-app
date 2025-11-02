package com.example.badmintonshop.ui;

import android.content.Intent;
import android.content.SharedPreferences;
import android.os.Bundle;
import android.view.View;
import android.widget.RadioGroup;
import android.widget.TextView;
import android.widget.Toast;
import android.util.Log;

import androidx.appcompat.app.AlertDialog;
import androidx.activity.result.ActivityResultLauncher;
import androidx.activity.result.contract.ActivityResultContracts;
import androidx.annotation.Nullable;
import androidx.appcompat.app.AppCompatActivity;
import androidx.recyclerview.widget.LinearLayoutManager;
import androidx.recyclerview.widget.RecyclerView;

import com.example.badmintonshop.R;
import com.example.badmintonshop.adapter.CheckoutProductAdapter;
import com.example.badmintonshop.model.CartItem;
import com.example.badmintonshop.network.ApiClient;
import com.example.badmintonshop.network.ApiService;
import com.example.badmintonshop.network.dto.AddressDto;
import com.example.badmintonshop.network.dto.AddressListResponse;
import com.example.badmintonshop.network.dto.ApiResponse;
import com.example.badmintonshop.network.dto.VoucherDto;
import com.example.badmintonshop.network.dto.ShippingRateDto;
import com.google.gson.Gson;
import com.google.android.material.button.MaterialButton;
import com.google.android.material.appbar.MaterialToolbar;

import java.math.BigDecimal;
import java.math.RoundingMode;
import java.util.ArrayList;
import java.util.List;
import java.util.Locale;

import retrofit2.Call;
import retrofit2.Callback;
import retrofit2.Response;

public class CheckoutActivity extends AppCompatActivity {

    private static final int SELECT_ADDRESS_REQUEST_CODE = 102;
    private static final int SELECT_VOUCHER_REQUEST_CODE = 103;
    private static final int SELECT_SHIPPING_REQUEST_CODE = 105;
    private static final String TAG = "CHECKOUT_DEBUG";

    private ArrayList<CartItem> selectedItems;
    private AddressDto selectedAddress;
    private VoucherDto selectedVoucher = null;
    private ShippingRateDto selectedShippingRate = null;
    private ApiService api;
    private TextView tvRecipientInfo, tvAddressDetails, tvSubtotal, tvShippingFee,
            tvTotalPayment, tvBottomTotal, tvVoucherCode, tvVoucherDiscount;
    private TextView tvShippingMethod, tvShippingEstimate;
    private View addressSection, voucherSection, shippingSection;
    private RadioGroup rgPaymentMethods;
    private MaterialButton btnPlaceOrder;
    private MaterialToolbar toolbar;

    // ⭐ LAUNCHER CHO PAYMENT ACTIVITY (Nhận kết quả từ VNPay WebView)
    private ActivityResultLauncher<Intent> paymentLauncher;

    // ⭐ LAUNCHER CHO PAYMENT FAILED ACTIVITY (Nhận "Thử lại")
    private ActivityResultLauncher<Intent> failureLauncher;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_checkout);

        // Lấy danh sách sản phẩm từ Intent
        selectedItems = (ArrayList<CartItem>) getIntent().getSerializableExtra("SELECTED_ITEMS");
        if (selectedItems == null || selectedItems.isEmpty()) {
            Toast.makeText(this, "Không có sản phẩm để thanh toán.", Toast.LENGTH_SHORT).show();
            finish();
            return;
        }

        api = ApiClient.getApiService();

        // ⭐ KHỞI TẠO FAILURE LAUNCHER TRƯỚC (Xử lý nút "Thử lại")
        failureLauncher = registerForActivityResult(
                new ActivityResultContracts.StartActivityForResult(),
                result -> {
                    if (result.getResultCode() == RESULT_OK) {
                        // User bấm "Thử lại" từ PaymentFailedActivity
                        int retryOrderId = result.getData() != null ?
                                result.getData().getIntExtra("RETRY_ORDER_ID", -1) :
                                -1;

                        if (retryOrderId != -1) {
                            Log.i(TAG, "User requested retry for OrderID: " + retryOrderId);
                            // ⭐ GỌI API REPAY
                            initiateRepayment(retryOrderId);
                        } else {
                            Log.w(TAG, "Retry requested but no valid OrderID.");
                        }
                    } else {
                        // User bấm "Quay về trang chủ" hoặc Back
                        Log.d(TAG, "Payment failure dismissed without retry.");
                        // Không làm gì, để user tự quay lại
                    }
                }
        );

        // ⭐ KHỞI TẠO PAYMENT LAUNCHER (Xử lý kết quả từ PaymentActivity)
        paymentLauncher = registerForActivityResult(
                new ActivityResultContracts.StartActivityForResult(),
                result -> {
                    // Lấy OrderID từ Intent trả về
                    String orderIdString = result.getData() != null ?
                            result.getData().getStringExtra("ORDER_ID") :
                            null;
                    int completedOrderId = (orderIdString != null) ? Integer.parseInt(orderIdString) : -1;

                    Log.d(TAG, "Payment result received. ResultCode: " + result.getResultCode() + ", OrderID: " + completedOrderId);

                    if (result.getResultCode() == RESULT_OK) {
                        // ✅ THANH TOÁN THÀNH CÔNG
                        Log.i(TAG, "Payment successful for OrderID: " + completedOrderId);
                        Toast.makeText(this, "Thanh toán thành công!", Toast.LENGTH_SHORT).show();

                        // Chuyển sang màn hình Success
                        Intent successIntent = new Intent(CheckoutActivity.this, PaymentSuccessActivity.class);
                        successIntent.putExtra("ORDER_ID", completedOrderId);
                        successIntent.addFlags(Intent.FLAG_ACTIVITY_CLEAR_TOP | Intent.FLAG_ACTIVITY_NEW_TASK);
                        startActivity(successIntent);
                        finish(); // Đóng CheckoutActivity

                    } else {
                        // ❌ THANH TOÁN THẤT BẠI
                        Log.w(TAG, "Payment failed/cancelled for OrderID: " + completedOrderId);

                        if (completedOrderId != -1) {
                            // Mở màn hình thất bại với nút "Thử lại"
                            Intent failureIntent = new Intent(CheckoutActivity.this, PaymentFailedActivity.class);
                            failureIntent.putExtra("ORDER_ID", completedOrderId);
                            failureLauncher.launch(failureIntent); // Dùng failureLauncher để nhận callback
                        } else {
                            Toast.makeText(this, "Thanh toán thất bại.", Toast.LENGTH_SHORT).show();
                        }
                    }
                }
        );

        // Setup UI
        bindViews();
        toolbar.setNavigationOnClickListener(v -> finish());

        // Click listeners cho các section
        addressSection.setOnClickListener(v -> {
            Intent intent = new Intent(CheckoutActivity.this, AddressActivity.class);
            intent.putExtra("IS_FOR_SELECTION", true);
            startActivityForResult(intent, SELECT_ADDRESS_REQUEST_CODE);
        });

        voucherSection.setOnClickListener(v -> {
            Intent intent = new Intent(CheckoutActivity.this, VoucherSelectionActivity.class);
            intent.putExtra("SUBTOTAL", calculateSubtotalValue().doubleValue());
            intent.putExtra("SELECTED_VOUCHER", selectedVoucher);
            startActivityForResult(intent, SELECT_VOUCHER_REQUEST_CODE);
        });

        shippingSection.setOnClickListener(v -> {
            if (calculateSubtotalValue().compareTo(BigDecimal.ZERO) <= 0) {
                Toast.makeText(CheckoutActivity.this, "Vui lòng thêm sản phẩm vào giỏ hàng.", Toast.LENGTH_SHORT).show();
                return;
            }
            Intent intent = new Intent(CheckoutActivity.this, ShippingSelectionActivity.class);
            String itemsJson = new Gson().toJson(selectedItems);
            intent.putExtra("ITEMS_JSON", itemsJson);
            startActivityForResult(intent, SELECT_SHIPPING_REQUEST_CODE);
        });

        setupProductList();
        fetchDefaultAddress();
        calculateAndDisplaySummary();
        btnPlaceOrder.setOnClickListener(v -> placeOrder());
    }

    private void bindViews() {
        toolbar = findViewById(R.id.toolbar);
        tvRecipientInfo = findViewById(R.id.tv_recipient_info);
        tvAddressDetails = findViewById(R.id.tv_address_details);
        tvSubtotal = findViewById(R.id.tv_subtotal);
        tvShippingFee = findViewById(R.id.tv_shipping_fee);
        tvTotalPayment = findViewById(R.id.tv_total_payment);
        tvBottomTotal = findViewById(R.id.tv_bottom_total);
        rgPaymentMethods = findViewById(R.id.rg_payment_methods);
        btnPlaceOrder = findViewById(R.id.btn_place_order);
        addressSection = findViewById(R.id.address_section_container);
        voucherSection = findViewById(R.id.voucher_section_container);
        tvVoucherCode = findViewById(R.id.tv_voucher_code);
        tvVoucherDiscount = findViewById(R.id.tv_voucher_discount);
        shippingSection = findViewById(R.id.shipping_section_container);
        tvShippingMethod = findViewById(R.id.tv_shipping_method);
        tvShippingEstimate = findViewById(R.id.tv_shipping_estimate);
    }

    private void setupProductList() {
        RecyclerView recyclerView = findViewById(R.id.recycler_checkout_products);
        recyclerView.setLayoutManager(new LinearLayoutManager(this));
        CheckoutProductAdapter adapter = new CheckoutProductAdapter(this, selectedItems);
        recyclerView.setAdapter(adapter);
    }

    private void fetchDefaultAddress() {
        int customerId = getCurrentCustomerId();
        if (customerId == -1) {
            Log.e(TAG, "Customer ID is -1. Cannot fetch addresses.");
            Toast.makeText(CheckoutActivity.this, "Vui lòng đăng nhập để tiếp tục.", Toast.LENGTH_LONG).show();
            return;
        }
        api.getAddresses(customerId).enqueue(new Callback<AddressListResponse>() {
            @Override
            public void onResponse(Call<AddressListResponse> call, Response<AddressListResponse> response) {
                if (response.isSuccessful() && response.body() != null && response.body().isSuccess()) {
                    List<AddressDto> addresses = response.body().getAddresses();
                    if (addresses != null) {
                        for (AddressDto address : addresses) {
                            if (address.isDefault()) {
                                selectedAddress = address;
                                displayAddress(address);
                                return;
                            }
                        }
                        if (!addresses.isEmpty()) {
                            selectedAddress = addresses.get(0);
                            displayAddress(selectedAddress);
                        } else {
                            Toast.makeText(CheckoutActivity.this, "Vui lòng thêm địa chỉ giao hàng", Toast.LENGTH_LONG).show();
                        }
                    }
                } else {
                    Log.e(TAG, "Failed to fetch addresses. Response code: " + response.code());
                }
            }

            @Override
            public void onFailure(Call<AddressListResponse> call, Throwable t) {
                Log.e(TAG, "Address fetch failed: " + t.getMessage());
            }
        });
    }

    private void displayAddress(AddressDto address) {
        String recipientInfo = address.getRecipientName() + " | " + address.getPhone();
        String fullAddress = address.getStreet() + ", " + address.getCity() + ", " + address.getCountry();
        tvRecipientInfo.setText(recipientInfo);
        tvAddressDetails.setText(fullAddress);
        Log.d(TAG, "Address displayed: " + address.getAddressID());
    }

    private void calculateAndDisplaySummary() {
        BigDecimal subtotal = calculateSubtotalValue();
        BigDecimal discount = calculateVoucherDiscount(subtotal);

        BigDecimal shippingFeeValue = (selectedShippingRate != null) ?
                BigDecimal.valueOf(selectedShippingRate.getShippingFee()) :
                BigDecimal.ZERO;

        BigDecimal total = subtotal.subtract(discount).add(shippingFeeValue);

        tvSubtotal.setText(String.format(Locale.GERMAN, "%,.0f đ", subtotal.doubleValue()));

        if (selectedVoucher != null && discount.compareTo(BigDecimal.ZERO) > 0) {
            tvVoucherCode.setText(selectedVoucher.getVoucherCode());
            tvVoucherDiscount.setText(String.format(Locale.GERMAN, "- %,.0f đ", discount.doubleValue()));
            tvVoucherDiscount.setTextColor(getResources().getColor(R.color.colorPrimary, getTheme()));
        } else {
            tvVoucherCode.setText("Chọn Voucher >");
            tvVoucherDiscount.setText(String.format(Locale.GERMAN, "%,.0f đ", 0.0));
            tvVoucherDiscount.setTextColor(getResources().getColor(R.color.colorSecondaryText, getTheme()));
        }

        if (selectedShippingRate != null) {
            String shipMethod = selectedShippingRate.getCarrierName() + " - " + selectedShippingRate.getServiceName();
            tvShippingMethod.setText(shipMethod);
            tvShippingEstimate.setText(String.format(Locale.GERMAN, "Dự kiến giao hàng: %s", selectedShippingRate.getEstimatedDelivery()));
            tvShippingFee.setText(String.format(Locale.GERMAN, "%,.0f đ", shippingFeeValue.doubleValue()));

            if (selectedShippingRate.isFreeShip()) {
                tvShippingFee.setText("Miễn phí");
                tvShippingFee.setTextColor(getResources().getColor(R.color.colorPrimary, getTheme()));
            } else {
                tvShippingFee.setTextColor(getResources().getColor(R.color.colorSecondaryText, getTheme()));
            }

        } else {
            tvShippingMethod.setText("Chọn phương thức vận chuyển >");
            tvShippingEstimate.setText("Thời gian giao hàng dự kiến...");
            tvShippingFee.setText("---");
            tvShippingFee.setTextColor(getResources().getColor(R.color.colorSecondaryText, getTheme()));
        }

        tvTotalPayment.setText(String.format(Locale.GERMAN, "%,.0f đ", total.doubleValue()));
        tvBottomTotal.setText(String.format(Locale.GERMAN, "%,.0f đ", total.doubleValue()));
        btnPlaceOrder.setEnabled(selectedAddress != null && selectedShippingRate != null && total.compareTo(BigDecimal.ZERO) >= 0);
        Log.d(TAG, "Summary calculated. Subtotal: " + subtotal + ", ShipFee: " + shippingFeeValue + ", Total: " + total.doubleValue());
    }

    private BigDecimal calculateSubtotalValue() {
        BigDecimal subtotal = BigDecimal.ZERO;
        for (CartItem item : selectedItems) {
            try {
                BigDecimal price = new BigDecimal(item.getVariantPrice());
                BigDecimal itemTotal = price.multiply(BigDecimal.valueOf(item.getQuantity()));
                subtotal = subtotal.add(itemTotal);
            } catch (NumberFormatException | NullPointerException e) {
                Log.e(TAG, "Price conversion error for item: " + item.getProductName() + ", Price: " + item.getVariantPrice(), e);
            }
        }
        return subtotal.setScale(0, RoundingMode.HALF_UP);
    }

    private BigDecimal calculateVoucherDiscount(BigDecimal subtotal) {
        if (selectedVoucher == null) {
            return BigDecimal.ZERO;
        }
        BigDecimal minOrderValue = selectedVoucher.getMinOrderValue();
        if (minOrderValue != null && minOrderValue.compareTo(BigDecimal.ZERO) > 0) {
            if (subtotal.compareTo(minOrderValue) < 0) {
                return BigDecimal.ZERO;
            }
        }
        BigDecimal discount = BigDecimal.ZERO;
        BigDecimal discountValue = selectedVoucher.getDiscountValue();
        BigDecimal maxDiscount = selectedVoucher.getMaxDiscountAmount();

        if ("percentage".equalsIgnoreCase(selectedVoucher.getDiscountType())) {
            BigDecimal percent = discountValue.divide(new BigDecimal("100"), 4, RoundingMode.HALF_UP);
            discount = subtotal.multiply(percent);
            if (maxDiscount != null && discount.compareTo(maxDiscount) > 0) {
                discount = maxDiscount;
            }
        } else if ("fixed".equalsIgnoreCase(selectedVoucher.getDiscountType())) {
            discount = discountValue;
        }
        if (discount.compareTo(subtotal) > 0) {
            discount = subtotal;
        }
        return discount.setScale(0, RoundingMode.DOWN);
    }

    private void placeOrder() {
        if (selectedAddress == null) {
            Toast.makeText(this, "Vui lòng chọn địa chỉ giao hàng", Toast.LENGTH_SHORT).show();
            Log.e(TAG, "Order failed: No address selected.");
            return;
        }
        if (selectedShippingRate == null) {
            Toast.makeText(this, "Vui lòng chọn phương thức vận chuyển", Toast.LENGTH_SHORT).show();
            return;
        }

        btnPlaceOrder.setEnabled(false);
        int customerId = getCurrentCustomerId();
        String paymentMethod;
        if (rgPaymentMethods.getCheckedRadioButtonId() == R.id.rb_cod) {
            paymentMethod = "COD";
        } else if (rgPaymentMethods.getCheckedRadioButtonId() == R.id.rb_vnpay) {
            paymentMethod = "VNPay";
        } else {
            Toast.makeText(this, "Vui lòng chọn phương thức thanh toán", Toast.LENGTH_SHORT).show();
            btnPlaceOrder.setEnabled(true);
            return;
        }

        BigDecimal subtotal = calculateSubtotalValue();
        BigDecimal discount = calculateVoucherDiscount(subtotal);
        BigDecimal shippingFeeValue = BigDecimal.valueOf(selectedShippingRate.getShippingFee());
        BigDecimal totalPayment = subtotal.subtract(discount).add(shippingFeeValue);
        int voucherId = selectedVoucher != null ? selectedVoucher.getVoucherID() : -1;
        int rateId = selectedShippingRate.getRateID();
        String itemsJson = new Gson().toJson(selectedItems);

        Log.d(TAG, String.format("📤 Placing Order. Rate ID: %d, Ship Fee: %.0f, Total: %.0f",
                rateId, shippingFeeValue.doubleValue(), totalPayment.doubleValue()));

        api.createOrder(
                customerId,
                selectedAddress.getAddressID(),
                paymentMethod,
                totalPayment.doubleValue(),
                itemsJson,
                voucherId,
                rateId,
                shippingFeeValue.doubleValue()
        ).enqueue(new Callback<ApiResponse>() {

            @Override
            public void onResponse(Call<ApiResponse> call, Response<ApiResponse> response) {
                btnPlaceOrder.setEnabled(true);
                Log.d(TAG, "📥 API Response Code: " + response.code());

                if (response.isSuccessful() && response.body() != null) {
                    // ✅ SERVER TRẢ VỀ 200 OK
                    if (response.body().isSuccess()) {
                        if ("VNPAY_REDIRECT".equalsIgnoreCase(response.body().getMessage())) {
                            // TRƯỜNG HỢP VNPAY
                            String vnpayUrl = response.body().getVnpayUrl();
                            int orderId = response.body().getOrderID();

                            if (vnpayUrl != null && !vnpayUrl.isEmpty()) {
                                Log.i(TAG, "🔗 Launching PaymentActivity (Create) with URL for OrderID: " + orderId);
                                Intent intent = new Intent(CheckoutActivity.this, PaymentActivity.class);
                                intent.putExtra("VNPAY_URL", vnpayUrl);
                                intent.putExtra("ORDER_ID_RET", String.valueOf(orderId)); // ⭐ Truyền OrderID
                                paymentLauncher.launch(intent); // ⭐ Dùng launcher
                            } else {
                                Toast.makeText(CheckoutActivity.this, "Lỗi tạo link thanh toán VNPay.", Toast.LENGTH_LONG).show();
                            }
                        } else {
                            // TRƯỜNG HỢP COD - Thành công ngay
                            Log.i(TAG, "✅ Order placed successfully (COD)! Launching Success Screen.");
                            Intent intent = new Intent(CheckoutActivity.this, PaymentSuccessActivity.class);
                            intent.putExtra("ORDER_ID", response.body().getOrderID());
                            intent.addFlags(Intent.FLAG_ACTIVITY_CLEAR_TOP | Intent.FLAG_ACTIVITY_NEW_TASK);
                            startActivity(intent);
                            finish();
                        }
                    } else {
                        // Server trả về 200 OK nhưng {isSuccess: false}
                        String errorMessage = response.body().getMessage();
                        Log.e(TAG, "⚠️ Order failed by server logic (200 OK). Message: " + errorMessage);
                        Toast.makeText(CheckoutActivity.this, errorMessage, Toast.LENGTH_LONG).show();
                    }
                } else {
                    // ❌ SERVER TRẢ VỀ LỖI (4xx, 5xx)
                    if (response.code() == 409) {
                        // ⚠️ LỖI 409 (Conflict) - GIÁ THAY ĐỔI
                        String errorMessage = "Giá hoặc khuyến mãi đã thay đổi";
                        try {
                            if (response.errorBody() != null) {
                                String errorJson = response.errorBody().string();
                                ApiResponse errorResponse = new Gson().fromJson(errorJson, ApiResponse.class);
                                if (errorResponse != null && errorResponse.getMessage() != null) {
                                    errorMessage = errorResponse.getMessage().replace("PRICE_MISMATCH: ", "");
                                }
                            }
                        } catch (Exception e) {
                            Log.e(TAG, "Failed to parse 409 error body", e);
                        }

                        Log.e(TAG, "⚠️ 409 Conflict: " + errorMessage);
                        showPriceMismatchDialog(errorMessage);

                    } else {
                        // Các lỗi 500, 400, 404... khác
                        String errorBody = "";
                        try {
                            if (response.errorBody() != null) {
                                errorBody = response.errorBody().string();
                            }
                        } catch (Exception e) {
                            errorBody = "Error body unreadable.";
                        }
                        Log.e(TAG, "❌ API call failed. HTTP Code: " + response.code() + ", Error Body: " + errorBody);
                        Toast.makeText(CheckoutActivity.this, "Đặt hàng thất bại. Vui lòng thử lại. (Code: " + response.code() + ")", Toast.LENGTH_LONG).show();
                    }
                }
            }

            @Override
            public void onFailure(Call<ApiResponse> call, Throwable t) {
                btnPlaceOrder.setEnabled(true);
                Log.e(TAG, "🔴 Network failure during placeOrder: " + t.getMessage(), t);
                Toast.makeText(CheckoutActivity.this, "Lỗi kết nối mạng: " + t.getMessage(), Toast.LENGTH_LONG).show();
            }
        });
    }

    // ⭐ HÀM HIỂN THỊ DIALOG KHI GIÁ THAY ĐỔI (Lỗi 409)
    private void showPriceMismatchDialog(String message) {
        new AlertDialog.Builder(this)
                .setTitle("😥 Có thay đổi về giá")
                .setMessage(message + "\n\nVui lòng quay lại giỏ hàng để kiểm tra và cập nhật lại đơn hàng của bạn.")
                .setPositiveButton("OK", (dialog, which) -> {
                    dialog.dismiss();
                    finish(); // Đóng CheckoutActivity để buộc user quay lại Cart
                })
                .setCancelable(false)
                .show();
    }

    // ⭐ HÀM INITIATE REPAYMENT (Được gọi khi user bấm "Thử lại")
    private void initiateRepayment(int orderId) {
        int customerId = getCurrentCustomerId();
        if (customerId <= 0) {
            Toast.makeText(this, "Lỗi xác thực người dùng.", Toast.LENGTH_LONG).show();
            return;
        }

        Toast.makeText(this, "Đang tạo lại link thanh toán...", Toast.LENGTH_SHORT).show();
        Log.d(TAG, "🔄 Calling Repay API for OrderID: " + orderId);

        api.repayOrder(customerId, orderId).enqueue(new Callback<ApiResponse>() {
            @Override
            public void onResponse(Call<ApiResponse> call, Response<ApiResponse> response) {
                if (response.isSuccessful() && response.body() != null && response.body().isSuccess()) {
                    if ("VNPAY_REDIRECT".equalsIgnoreCase(response.body().getMessage())) {
                        String vnpayUrl = response.body().getVnpayUrl();
                        if (vnpayUrl != null && !vnpayUrl.isEmpty()) {
                            Log.i(TAG, "✅ Launching PaymentActivity (Repay) with URL");
                            Intent intent = new Intent(CheckoutActivity.this, PaymentActivity.class);
                            intent.putExtra("VNPAY_URL", vnpayUrl);
                            intent.putExtra("ORDER_ID_RET", String.valueOf(orderId));
                            paymentLauncher.launch(intent);
                        } else {
                            Toast.makeText(CheckoutActivity.this, "Lỗi: Không nhận được URL VNPay.", Toast.LENGTH_LONG).show();
                        }
                    } else {
                        Toast.makeText(CheckoutActivity.this, response.body().getMessage(), Toast.LENGTH_LONG).show();
                    }
                } else {
                    String errorMsg = parseErrorMessage(response);
                    Toast.makeText(CheckoutActivity.this, "Không thể thanh toán lại: " + errorMsg, Toast.LENGTH_LONG).show();
                    Log.e(TAG, "❌ Repay API failed: " + errorMsg + " (Code: " + response.code() + ")");
                }
            }

            @Override
            public void onFailure(Call<ApiResponse> call, Throwable t) {
                Toast.makeText(CheckoutActivity.this, "Lỗi kết nối mạng: " + t.getMessage(), Toast.LENGTH_LONG).show();
                Log.e(TAG, "🔴 Repay network failure: ", t);
            }
        });
    }

    // ⭐ HÀM PARSE ERROR MESSAGE
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
        }
        return defaultError;
    }

    private int getCurrentCustomerId() {
        SharedPreferences prefs = getSharedPreferences("auth", MODE_PRIVATE);
        return prefs.getInt("customerID", -1);
    }

    @Override
    protected void onActivityResult(int requestCode, int resultCode, @Nullable Intent data) {
        super.onActivityResult(requestCode, resultCode, data);

        if (resultCode != RESULT_OK || data == null) {
            if (requestCode == SELECT_VOUCHER_REQUEST_CODE) {
                this.selectedVoucher = null;
                calculateAndDisplaySummary();
            }
            if (requestCode == SELECT_SHIPPING_REQUEST_CODE && this.selectedShippingRate == null) {
                calculateAndDisplaySummary();
            }
            return;
        }

        if (requestCode == SELECT_ADDRESS_REQUEST_CODE) {
            AddressDto returnedAddress = (AddressDto) data.getSerializableExtra("SELECTED_ADDRESS");
            if (returnedAddress != null) {
                this.selectedAddress = returnedAddress;
                displayAddress(returnedAddress);
                calculateAndDisplaySummary();
            }
        } else if (requestCode == SELECT_VOUCHER_REQUEST_CODE) {
            VoucherDto returnedVoucher = (VoucherDto) data.getSerializableExtra("SELECTED_VOUCHER");
            this.selectedVoucher = returnedVoucher;
            calculateAndDisplaySummary();
        } else if (requestCode == SELECT_SHIPPING_REQUEST_CODE) {
            ShippingRateDto returnedRate = (ShippingRateDto) data.getSerializableExtra("SELECTED_SHIPPING_RATE");
            this.selectedShippingRate = returnedRate;
            calculateAndDisplaySummary();
        }
    }
}