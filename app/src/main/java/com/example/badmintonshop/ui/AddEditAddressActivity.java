package com.example.badmintonshop.ui;

import android.content.Intent;
import android.content.SharedPreferences;
import android.os.Bundle;
import android.widget.Toast;
import androidx.appcompat.app.AppCompatActivity;

import com.example.badmintonshop.R;
import com.example.badmintonshop.network.ApiClient;
import com.example.badmintonshop.network.ApiService;
import com.example.badmintonshop.network.dto.AddressDto;
import com.example.badmintonshop.network.dto.ApiResponse;
import com.google.android.material.appbar.MaterialToolbar;
import com.google.android.material.button.MaterialButton;
import com.google.android.material.textfield.TextInputEditText;

import retrofit2.Call;
import retrofit2.Callback;
import retrofit2.Response;

public class AddEditAddressActivity extends AppCompatActivity {

    private TextInputEditText etRecipientName, etPhone, etStreet, etCity, etPostalCode, etCountry;
    private MaterialButton btnSaveAddress;
    private MaterialToolbar toolbar;
    private ApiService api;

    private AddressDto existingAddress = null; // Biến để lưu địa chỉ cũ nếu là chế độ sửa

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_add_edit_address);

        // Ánh xạ view
        toolbar = findViewById(R.id.toolbar);
        etRecipientName = findViewById(R.id.et_recipient_name);
        etPhone = findViewById(R.id.et_phone);
        etStreet = findViewById(R.id.et_street);
        etCity = findViewById(R.id.et_city);
        etPostalCode = findViewById(R.id.et_postal_code);
        etCountry = findViewById(R.id.et_country);
        btnSaveAddress = findViewById(R.id.btn_save_address);
        api = ApiClient.getApiService();

        toolbar.setNavigationOnClickListener(v -> finish());

        // Kiểm tra xem có dữ liệu địa chỉ được truyền qua Intent không
        if (getIntent().hasExtra("EDIT_ADDRESS")) {
            existingAddress = (AddressDto) getIntent().getSerializableExtra("EDIT_ADDRESS");
            toolbar.setTitle("Sửa địa chỉ");
            populateFields(existingAddress);
        } else {
            toolbar.setTitle("Thêm địa chỉ mới");
        }

        btnSaveAddress.setOnClickListener(v -> saveAddress());
    }

    // Điền thông tin cũ vào form nếu là chế độ sửa
    private void populateFields(AddressDto address) {
        etRecipientName.setText(address.getRecipientName());
        etPhone.setText(address.getPhone());
        etStreet.setText(address.getStreet());
        etCity.setText(address.getCity());
        etPostalCode.setText(address.getPostalCode());
        etCountry.setText(address.getCountry());
    }

    private int getCurrentCustomerId() {
        SharedPreferences prefs = getSharedPreferences("auth", MODE_PRIVATE);
        return prefs.getInt("customerID", -1);
    }

    // Hàm lưu địa chỉ (gọi API thêm hoặc sửa)
    private void saveAddress() {
        String recipientName = etRecipientName.getText().toString().trim();
        String phone = etPhone.getText().toString().trim();
        String street = etStreet.getText().toString().trim();
        String city = etCity.getText().toString().trim();
        String postalCode = etPostalCode.getText().toString().trim();
        String country = etCountry.getText().toString().trim();
        int customerId = getCurrentCustomerId();

        // Kiểm tra dữ liệu đầu vào
        if (recipientName.isEmpty() || phone.isEmpty() || street.isEmpty() || city.isEmpty() || country.isEmpty()) {
            Toast.makeText(this, "Vui lòng điền đầy đủ thông tin", Toast.LENGTH_SHORT).show();
            return;
        }

        Call<ApiResponse> apiCall;
        if (existingAddress != null) {
            // Chế độ Sửa: Gọi API update
            apiCall = api.updateAddress(existingAddress.getAddressID(), customerId, recipientName, phone, street, city, postalCode, country);
        } else {
            // Chế độ Thêm: Gọi API add
            apiCall = api.addAddress(customerId, recipientName, phone, street, city, postalCode, country);
        }

        apiCall.enqueue(new Callback<ApiResponse>() {
            @Override
            public void onResponse(Call<ApiResponse> call, Response<ApiResponse> response) {
                if (response.isSuccessful() && response.body() != null) {
                    if (response.body().isSuccess()) {
                        // Thao tác thành công
                        String successMessage = existingAddress != null ? "Sửa địa chỉ thành công!" : "Thêm địa chỉ thành công!";
                        Toast.makeText(AddEditAddressActivity.this, successMessage, Toast.LENGTH_SHORT).show();
                        setResult(RESULT_OK); // Báo cho AddressActivity biết để tải lại danh sách
                        finish(); // Đóng màn hình này
                    } else {
                        // Thao tác thất bại do logic server (isSuccess=false)
                        String errorMessage = response.body().getMessage();
                        Toast.makeText(AddEditAddressActivity.this, "Lưu thất bại: " + (errorMessage != null ? errorMessage : "Lỗi server không rõ."), Toast.LENGTH_LONG).show();
                    }
                } else {
                    // Thao tác thất bại do lỗi HTTP (4xx, 5xx) hoặc lỗi parse JSON
                    String errorDetail = "Lưu thất bại.";
                    try {
                        String rawError = response.errorBody() != null ? response.errorBody().string() : null;
                        if (rawError != null && !rawError.isEmpty()) {
                            // Thử parse lỗi HTTP thành JSON (nếu server có trả về JSON lỗi)
                            errorDetail = "Lỗi HTTP " + response.code();
                        } else {
                            errorDetail = "Lỗi HTTP " + response.code() + ".";
                        }
                    } catch (Exception e) {
                        errorDetail = "Lỗi kết nối hoặc đọc phản hồi.";
                    }
                    Toast.makeText(AddEditAddressActivity.this, errorDetail, Toast.LENGTH_LONG).show();
                }
            }

            @Override
            public void onFailure(Call<ApiResponse> call, Throwable t) {
                Toast.makeText(AddEditAddressActivity.this, "Lỗi kết nối: " + t.getMessage(), Toast.LENGTH_SHORT).show();
            }
        });
    }
}
