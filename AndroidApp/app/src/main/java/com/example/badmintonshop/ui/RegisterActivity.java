package com.example.badmintonshop.ui;

import android.os.Bundle;
import android.util.Log;
import android.widget.Toast;
import androidx.appcompat.app.AppCompatActivity;

import com.example.badmintonshop.R;
import com.example.badmintonshop.network.ApiClient;
import com.example.badmintonshop.network.ApiService;
import com.example.badmintonshop.network.dto.AuthRegisterBody;
import com.example.badmintonshop.network.dto.AuthResponse;
import com.google.android.material.button.MaterialButton;
import com.google.android.material.textfield.TextInputEditText;

import java.io.IOException;

import retrofit2.Call;
import retrofit2.Callback;
import retrofit2.Response;

public class RegisterActivity extends AppCompatActivity {

    private static final String TAG = "RegisterActivityDebug";
    private ApiService api;

    private TextInputEditText etFullName, etPhone, etAddress, etEmail, etPassword, etPassword2;
    private MaterialButton btnRegister;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_register);

        api = ApiClient.getApiService();

        // Liên kết view
        etFullName = findViewById(R.id.etFullNameRegister);
        etPhone = findViewById(R.id.etPhoneRegister);
        etAddress = findViewById(R.id.etAddressRegister);
        etEmail = findViewById(R.id.etEmailRegister);
        etPassword = findViewById(R.id.etPasswordRegister);
        etPassword2 = findViewById(R.id.etConfirmPassword);
        btnRegister = findViewById(R.id.btnRegister);

        btnRegister.setOnClickListener(v -> doRegister());
    }

    private void doRegister() {
        // ⭐ Cải tiến: Sử dụng .toString() an toàn hơn và trim
        String fullName = etFullName.getText() != null ? etFullName.getText().toString().trim() : "";
        String phone = etPhone.getText() != null ? etPhone.getText().toString().trim() : "";
        String address = etAddress.getText() != null ? etAddress.getText().toString().trim() : "";
        String email = etEmail.getText() != null ? etEmail.getText().toString().trim() : "";
        String pass1 = etPassword.getText() != null ? etPassword.getText().toString().trim() : "";
        String pass2 = etPassword2.getText() != null ? etPassword2.getText().toString().trim() : "";


        // --- Validate cơ bản ---
        if (fullName.isEmpty() || phone.isEmpty() || address.isEmpty() ||
                email.isEmpty() || pass1.isEmpty() || pass2.isEmpty()) {
            toast("Vui lòng điền đầy đủ thông tin");
            return;
        }

        if (!android.util.Patterns.EMAIL_ADDRESS.matcher(email).matches()) {
            toast("Email không hợp lệ");
            return;
        }

        if (!pass1.equals(pass2)) {
            toast("Mật khẩu xác nhận không khớp");
            return;
        }

        if (pass1.length() < 6) {
            toast("Mật khẩu phải có ít nhất 6 ký tự");
            return;
        }
        // -------------------------

        btnRegister.setEnabled(false); // Vô hiệu hóa nút

        // Gửi request
        AuthRegisterBody body = new AuthRegisterBody(fullName, email, pass1, phone, address);

        api.register(body).enqueue(new Callback<AuthResponse>() {
            @Override
            public void onResponse(Call<AuthResponse> call, Response<AuthResponse> response) {
                btnRegister.setEnabled(true); // Bật lại nút

                if (!response.isSuccessful()) {
                    String errorMessage = "Lỗi HTTP " + response.code();
                    try {
                        if (response.errorBody() != null) {
                            // Cố gắng đọc lỗi từ server
                            String error = response.errorBody().string();
                            Log.e(TAG, "HTTP Error Detail: " + error);
                            // Bạn có thể parse JSON lỗi ở đây nếu cần
                        }
                    } catch (IOException e) {
                        Log.e(TAG, "Error reading error body: ", e);
                    }
                    toast("Đăng ký thất bại. Lỗi hệ thống.");
                    return;
                }

                AuthResponse res = response.body();
                if (res == null) {
                    toast("Phản hồi server không hợp lệ.");
                    return;
                }

                // ⭐ Xử lý các trường hợp thành công/thất bại theo message
                if ("registered".equalsIgnoreCase(res.getMessage()) ||
                        "ok".equalsIgnoreCase(res.getMessage())) {
                    toast("Đăng ký thành công! Vui lòng đăng nhập.");
                    finish(); // Quay về LoginActivity
                } else if ("email_exists".equalsIgnoreCase(res.getMessage()) ||
                        "email_exists".equalsIgnoreCase(res.getError())) {
                    toast("Email đã tồn tại. Vui lòng sử dụng email khác.");
                } else {
                    // Trường hợp lỗi khác từ server (ví dụ: lỗi validate chi tiết)
                    String serverMessage = res.getMessage() != null ? res.getMessage() : "Lỗi server không xác định.";
                    toast("Đăng ký thất bại: " + serverMessage);
                    Log.w(TAG, "Registration failed with message: " + serverMessage);
                }
            }

            @Override
            public void onFailure(Call<AuthResponse> call, Throwable t) {
                btnRegister.setEnabled(true); // Bật lại nút
                toast("Không kết nối được server: " + t.getMessage());
                Log.e(TAG, "Network Failure: ", t);
            }
        });
    }

    private void toast(String msg) {
        Toast.makeText(this, msg, Toast.LENGTH_SHORT).show();
    }
}