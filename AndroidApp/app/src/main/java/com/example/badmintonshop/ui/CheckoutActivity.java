package com.example.badmintonshop.ui;

import android.content.Intent;
import android.content.SharedPreferences;
import android.os.Bundle;
import android.view.View;
import android.widget.RadioGroup;
import android.widget.TextView;
import android.widget.Toast;
import android.util.Log;
// ⭐ MỚI: Thêm Uri để mở trình duyệt
import android.net.Uri;

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
    // ⭐ ĐÃ THÊM: Request code cho VNPay
    private static final int VNPAY_REQUEST_CODE = 104;
    private static final String TAG = "CHECKOUT_DEBUG";

    private ArrayList<CartItem> selectedItems;
    private AddressDto selectedAddress;
    private VoucherDto selectedVoucher = null;
    private final double shippingFee = 22200; // Phí ship cố định

    private ApiService api;
    private TextView tvRecipientInfo, tvAddressDetails, tvSubtotal, tvShippingFee,
            tvTotalPayment, tvBottomTotal, tvVoucherCode, tvVoucherDiscount;
    private RadioGroup rgPaymentMethods;
    private MaterialButton btnPlaceOrder;
    private MaterialToolbar toolbar;
    private View addressSection, voucherSection;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_checkout);

        selectedItems = (ArrayList<CartItem>) getIntent().getSerializableExtra("SELECTED_ITEMS");
        if (selectedItems == null || selectedItems.isEmpty()) {
            Toast.makeText(this, "Không có sản phẩm để thanh toán.", Toast.LENGTH_SHORT).show();
            finish();
            return;
        }

        api = ApiClient.getApiService();
        bindViews();
        toolbar.setNavigationOnClickListener(v -> finish());

        addressSection.setOnClickListener(v -> {
            Intent intent = new Intent(CheckoutActivity.this, AddressActivity.class);
            intent.putExtra("IS_FOR_SELECTION", true);
            startActivityForResult(intent, SELECT_ADDRESS_REQUEST_CODE);
        });

        voucherSection.setOnClickListener(v -> {
            Intent intent = new Intent(CheckoutActivity.this, VoucherSelectionActivity.class);

            // Truyền subtotal thực tế
            intent.putExtra("SUBTOTAL", calculateSubtotalValue().doubleValue());

            intent.putExtra("SELECTED_VOUCHER", selectedVoucher);
            startActivityForResult(intent, SELECT_VOUCHER_REQUEST_CODE);
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
                            // Có thể mở AddressActivity ở đây nếu muốn người dùng thêm ngay
                        }
                    }
                } else {
                    Log.e(TAG, "Failed to fetch addresses. Response code: " + response.code());
                }
            }
            @Override public void onFailure(Call<AddressListResponse> call, Throwable t) {
                Log.e(TAG, "Address fetch failed: " + t.getMessage());
            }
        });
    }

    private void displayAddress(AddressDto address) {
        String recipientInfo = address.getRecipientName() + " | " + address.getPhone();
        // Lấy thông tin địa chỉ chi tiết hơn
        String fullAddress = address.getStreet() + ", " + address.getCity() + ", " + address.getCountry();
        tvRecipientInfo.setText(recipientInfo);
        tvAddressDetails.setText(fullAddress);
        Log.d(TAG, "Address displayed: " + address.getAddressID());
    }

    private void calculateAndDisplaySummary() {
        BigDecimal subtotal = calculateSubtotalValue();
        BigDecimal discount = calculateVoucherDiscount(subtotal);
        BigDecimal total = subtotal.subtract(discount).add(BigDecimal.valueOf(shippingFee));

        tvSubtotal.setText(String.format(Locale.GERMAN, "%,.0f đ", subtotal.doubleValue()));

        if (selectedVoucher != null && discount.compareTo(BigDecimal.ZERO) > 0) {
            tvVoucherCode.setText(selectedVoucher.getVoucherCode());
            tvVoucherDiscount.setText(String.format(Locale.GERMAN, "- %,.0f đ", discount.doubleValue()));
            Log.d(TAG, "Voucher applied: " + selectedVoucher.getVoucherCode() + ", Discount: " + discount);
        } else {
            tvVoucherCode.setText("Chọn Voucher >");
            tvVoucherDiscount.setText(String.format(Locale.GERMAN, "%,.0f đ", 0.0));
            if (selectedVoucher != null) {
                Log.d(TAG, "Voucher code selected but not applied (Min order value not met).");
            }
        }

        tvShippingFee.setText(String.format(Locale.GERMAN, "%,.0f đ", shippingFee));
        tvTotalPayment.setText(String.format(Locale.GERMAN, "%,.0f đ", total.doubleValue()));
        tvBottomTotal.setText(String.format(Locale.GERMAN, "%,.0f đ", total.doubleValue()));

        // Kích hoạt/Vô hiệu hóa nút đặt hàng
        btnPlaceOrder.setEnabled(selectedAddress != null && total.compareTo(BigDecimal.ZERO) >= 0);

        Log.d(TAG, "Summary calculated. Subtotal: " + subtotal + ", Total: " + total.doubleValue());
    }

    private BigDecimal calculateSubtotalValue() {
        BigDecimal subtotal = BigDecimal.ZERO;
        for (CartItem item : selectedItems) {
            try {
                // SỬA: Đảm bảo CartItem.getVariantPrice() trả về chuỗi hợp lệ
                BigDecimal price = new BigDecimal(item.getVariantPrice());
                BigDecimal itemTotal = price.multiply(BigDecimal.valueOf(item.getQuantity()));
                subtotal = subtotal.add(itemTotal);
            } catch (NumberFormatException | NullPointerException e) {
                // Xử lý lỗi nếu giá không phải là số hoặc null
                Log.e(TAG, "Price conversion error for item: " + item.getProductName() + ", Price: " + item.getVariantPrice(), e);
            }
        }
        return subtotal.setScale(0, RoundingMode.HALF_UP); // Làm tròn subtotal trước khi tính toán
    }

    private BigDecimal calculateVoucherDiscount(BigDecimal subtotal) {
        if (selectedVoucher == null) {
            return BigDecimal.ZERO;
        }

        // ⭐ Cải tiến: Kiểm tra MinOrderValue. Nếu MinOrderValue là null hoặc <= 0, coi như không có giới hạn
        BigDecimal minOrderValue = selectedVoucher.getMinOrderValue();
        if (minOrderValue != null && minOrderValue.compareTo(BigDecimal.ZERO) > 0) {
            if (subtotal.compareTo(minOrderValue) < 0) {
                return BigDecimal.ZERO; // Không đạt giá trị đơn hàng tối thiểu
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

        // Chiết khấu không được lớn hơn subtotal
        if (discount.compareTo(subtotal) > 0) {
            discount = subtotal;
        }

        return discount.setScale(0, RoundingMode.DOWN); // Làm tròn xuống để hiển thị
    }

    private void placeOrder() {
        if (selectedAddress == null) {
            Toast.makeText(this, "Vui lòng chọn địa chỉ giao hàng", Toast.LENGTH_SHORT).show();
            Log.e(TAG, "Order failed: No address selected.");
            return;
        }

        btnPlaceOrder.setEnabled(false); // Vô hiệu hóa nút để tránh gửi trùng lặp

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
        BigDecimal totalPayment = subtotal.subtract(discount).add(BigDecimal.valueOf(shippingFee));

        // Lấy Voucher ID (hoặc -1 nếu không có)
        int voucherId = selectedVoucher != null ? selectedVoucher.getVoucherID() : -1;

        // Cần đảm bảo CartItem là Serializable hoặc sử dụng Parcelable để Gson hoạt động đúng
        String itemsJson = new Gson().toJson(selectedItems);

        // LOG CÁC THAM SỐ GỬI ĐI
        Log.d(TAG, "Placing Order...");
        Log.d(TAG, String.format("Customer ID: %d, Address ID: %d, Payment: %s, Total: %.0f, Voucher ID: %d",
                customerId, selectedAddress.getAddressID(), paymentMethod, totalPayment.doubleValue(), voucherId));

        // Lời gọi API với 6 tham số
        api.createOrder(
                customerId,
                selectedAddress.getAddressID(),
                paymentMethod,
                totalPayment.doubleValue(),
                itemsJson,
                voucherId // Tham số thứ 6
        ).enqueue(new Callback<ApiResponse>() {
            @Override
            public void onResponse(Call<ApiResponse> call, Response<ApiResponse> response) {
                btnPlaceOrder.setEnabled(true); // Bật lại nút
                Log.d(TAG, "API Response Code: " + response.code());

                if (response.isSuccessful() && response.body() != null) {
                    if (response.body().isSuccess()) {
                        // ⭐ START VNPAY INTEGRATION ⭐
                        if ("VNPAY_REDIRECT".equalsIgnoreCase(response.body().getMessage())) {
                            String vnpayUrl = response.body().  getVnpayUrl(); // Cần thêm getVnpayUrl() vào ApiResponse DTO

                            if (vnpayUrl != null && !vnpayUrl.isEmpty()) {
                                Log.i(TAG, "Redirecting to VNPAY: " + vnpayUrl);

                                // Mở URL VNPay trong trình duyệt (sẽ trả về kết quả qua vnpay_return.php)
                                Intent browserIntent = new Intent(Intent.ACTION_VIEW, Uri.parse(vnpayUrl));
                                startActivityForResult(browserIntent, VNPAY_REQUEST_CODE);

                            } else {
                                Toast.makeText(CheckoutActivity.this, "Lỗi tạo link thanh toán VNPay.", Toast.LENGTH_LONG).show();
                            }

                        } else {
                            // Xử lý COD (Đặt hàng thành công, email đã gửi)
                            Log.i(TAG, "Order placed successfully! Message: " + response.body().getMessage());
                            Toast.makeText(CheckoutActivity.this, "Đặt hàng thành công!", Toast.LENGTH_LONG).show();
                            // Chuyển hướng về trang chủ và xóa hết stack
                            Intent intent = new Intent(CheckoutActivity.this, HomeActivity.class);
                            intent.addFlags(Intent.FLAG_ACTIVITY_CLEAR_TOP | Intent.FLAG_ACTIVITY_NEW_TASK);
                            startActivity(intent);
                            finish();
                        }
                        // ⭐ END VNPAY INTEGRATION ⭐

                    } else {
                        // LOG LỖI TỪ SERVER
                        String errorMessage = response.body().getMessage();
                        Log.e(TAG, "Order failed by server logic. Message: " + errorMessage);
                        Toast.makeText(CheckoutActivity.this, errorMessage, Toast.LENGTH_LONG).show();
                    }
                } else {
                    // LOG LỖI HTTP
                    String errorBody = "";
                    try {
                        if (response.errorBody() != null) {
                            errorBody = response.errorBody().string();
                        }
                    } catch (Exception e) {
                        errorBody = "Error body unreadable.";
                    }
                    Log.e(TAG, "API call failed. HTTP Code: " + response.code() + ", Error Body: " + errorBody);
                    Toast.makeText(CheckoutActivity.this, "Đặt hàng thất bại. Vui lòng thử lại. (Code: " + response.code() + ")", Toast.LENGTH_LONG).show();
                }
            }
            @Override
            public void onFailure(Call<ApiResponse> call, Throwable t) {
                btnPlaceOrder.setEnabled(true); // Bật lại nút
                // LOG LỖI KẾT NỐI
                Log.e(TAG, "Network failure during placeOrder: " + t.getMessage(), t);
                Toast.makeText(CheckoutActivity.this, "Lỗi kết nối mạng: " + t.getMessage(), Toast.LENGTH_LONG).show();
            }
        });
    }

    private int getCurrentCustomerId() {
        SharedPreferences prefs = getSharedPreferences("auth", MODE_PRIVATE);
        return prefs.getInt("customerID", -1);
    }

    @Override
    protected void onActivityResult(int requestCode, int resultCode, @Nullable Intent data) {
        super.onActivityResult(requestCode, resultCode, data);

        // ⭐ XỬ LÝ KẾT QUẢ VNPAY ⭐
        if (requestCode == VNPAY_REQUEST_CODE) {
            // Sau khi người dùng thoát khỏi trình duyệt/WebView VNPay (dù thành công hay thất bại)
            Toast.makeText(this, "Hoàn tất thanh toán. Vui lòng kiểm tra trạng thái đơn hàng.", Toast.LENGTH_LONG).show();

            // Chuyển hướng về trang đơn hàng của tôi
            Intent intent = new Intent(this, YourOrdersActivity.class);
            intent.addFlags(Intent.FLAG_ACTIVITY_CLEAR_TOP | Intent.FLAG_ACTIVITY_NEW_TASK);
            startActivity(intent);
            finish();
            return;
        }

        // --- Xử lý Address và Voucher (Giữ nguyên) ---
        if (resultCode != RESULT_OK || data == null) {
            // Nếu người dùng đóng màn hình chọn voucher/địa chỉ mà không chọn gì,
            // nếu đó là màn hình voucher, cần hủy chọn voucher nếu có.
            if (requestCode == SELECT_VOUCHER_REQUEST_CODE) {
                this.selectedVoucher = null;
                calculateAndDisplaySummary();
            }
            return;
        }

        if (requestCode == SELECT_ADDRESS_REQUEST_CODE) {
            AddressDto returnedAddress = (AddressDto) data.getSerializableExtra("SELECTED_ADDRESS");
            if (returnedAddress != null) {
                this.selectedAddress = returnedAddress;
                displayAddress(returnedAddress);
                calculateAndDisplaySummary(); // Cập nhật tổng tiền (chủ yếu để bật nút đặt hàng)
            }
        } else if (requestCode == SELECT_VOUCHER_REQUEST_CODE) {
            // Nhận đối tượng VoucherDto (có thể là null nếu người dùng chọn "Không dùng voucher")
            VoucherDto returnedVoucher = (VoucherDto) data.getSerializableExtra("SELECTED_VOUCHER");
            this.selectedVoucher = returnedVoucher;
            calculateAndDisplaySummary();
        }
    }
}
