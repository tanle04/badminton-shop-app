package com.example.badmintonshop.ui;

import android.os.Bundle;
import android.widget.Toast;
import androidx.appcompat.app.AppCompatActivity;

import com.example.badmintonshop.R;
import com.example.badmintonshop.network.ApiClient;
import com.example.badmintonshop.network.ApiService;
import com.example.badmintonshop.network.dto.AuthRegisterBody;
import com.example.badmintonshop.network.dto.AuthResponse;
import com.google.android.material.button.MaterialButton;
import com.google.android.material.textfield.TextInputEditText;

import retrofit2.Call;
import retrofit2.Callback;
import retrofit2.Response;

public class RegisterActivity extends AppCompatActivity {

    private static final String BASE_URL = "http://10.0.2.2/api/BadmintonShop/"; // Dùng IP LAN nếu chạy máy thật
    private ApiService api;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_register);

        api = ApiClient.get(BASE_URL).create(ApiService.class);

        // 🧩 Liên kết view
        TextInputEditText etFullName = findViewById(R.id.etFullNameRegister);
        TextInputEditText etPhone = findViewById(R.id.etPhoneRegister);
        TextInputEditText etAddress = findViewById(R.id.etAddressRegister);
        TextInputEditText etEmail = findViewById(R.id.etEmailRegister);
        TextInputEditText etPassword = findViewById(R.id.etPasswordRegister);
        TextInputEditText etPassword2 = findViewById(R.id.etConfirmPassword);
        MaterialButton btnRegister = findViewById(R.id.btnRegister);

        btnRegister.setOnClickListener(v -> {
            String fullName = String.valueOf(etFullName.getText()).trim();
            String phone = String.valueOf(etPhone.getText()).trim();
            String address = String.valueOf(etAddress.getText()).trim();
            String email = String.valueOf(etEmail.getText()).trim();
            String pass1 = String.valueOf(etPassword.getText()).trim();
            String pass2 = String.valueOf(etPassword2.getText()).trim();

            // 🔍 Validate cơ bản
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
                toast("Mật khẩu không khớp");
                return;
            }

            if (pass1.length() < 6) {
                toast("Mật khẩu phải có ít nhất 6 ký tự");
                return;
            }

            // 📨 Gửi request
            AuthRegisterBody body = new AuthRegisterBody(fullName, email, pass1, phone, address);

            api.register(body).enqueue(new Callback<AuthResponse>() {
                @Override
                public void onResponse(Call<AuthResponse> call, Response<AuthResponse> response) {
                    if (!response.isSuccessful() || response.body() == null) {
                        toast("Lỗi server, vui lòng thử lại");
                        return;
                    }

                    AuthResponse res = response.body();
                    if ("registered".equalsIgnoreCase(res.getMessage()) ||
                            "ok".equalsIgnoreCase(res.getMessage())) {
                        toast("Đăng ký thành công!");
                        finish(); // Quay về LoginActivity
                    } else if ("email_exists".equalsIgnoreCase(res.getMessage()) ||
                            "email_exists".equalsIgnoreCase(res.getError())) {
                        toast("Email đã tồn tại");
                    } else {
                        toast("Đăng ký thất bại, vui lòng kiểm tra lại");
                    }
                }

                @Override
                public void onFailure(Call<AuthResponse> call, Throwable t) {
                    toast("Không kết nối được server: " + t.getMessage());
                }
            });
        });
    }

    private void toast(String msg) {
        Toast.makeText(this, msg, Toast.LENGTH_SHORT).show();
    }
}
