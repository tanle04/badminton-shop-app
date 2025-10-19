package com.example.badmintonshop.ui;

import android.content.Intent;
import android.content.SharedPreferences;
import android.os.Bundle;
import android.util.Log;
import android.widget.TextView;
import android.widget.Toast;

import androidx.appcompat.app.AppCompatActivity;

import com.example.badmintonshop.R;
import com.example.badmintonshop.network.ApiClient;
import com.example.badmintonshop.network.ApiService;
import com.example.badmintonshop.network.dto.AuthLoginBody;
import com.example.badmintonshop.network.dto.AuthResponse;
import com.google.android.material.button.MaterialButton;
import com.google.android.material.textfield.TextInputEditText;

import java.io.IOException;

import retrofit2.Call;
import retrofit2.Callback;
import retrofit2.Response;

public class LoginActivity extends AppCompatActivity {

    private static final String TAG = "LoginActivityDebug";
    private ApiService api;
    private TextInputEditText etEmail, etPassword;
    private MaterialButton btnLogin;
    private TextView tvRegister;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_login);

        api = ApiClient.getApiService();

        etEmail = findViewById(R.id.etEmail);
        etPassword = findViewById(R.id.etPassword);
        btnLogin = findViewById(R.id.btnLogin);
        tvRegister = findViewById(R.id.tvRegister);

        btnLogin.setOnClickListener(v -> doLogin());
        tvRegister.setOnClickListener(v ->
                startActivity(new Intent(this, RegisterActivity.class))
        );
    }

    private void doLogin() {
        // ⭐ Cải tiến: Sử dụng .toString() an toàn hơn trên đối tượng Editable
        String email = etEmail.getText() != null ? etEmail.getText().toString().trim() : "";
        String pass  = etPassword.getText() != null ? etPassword.getText().toString() : "";

        if (email.isEmpty() || pass.isEmpty()) {
            toast("Vui lòng nhập đầy đủ email và mật khẩu");
            return;
        }

        btnLogin.setEnabled(false);

        AuthLoginBody body = new AuthLoginBody();
        body.email = email;
        body.password = pass;

        api.login(body).enqueue(new Callback<AuthResponse>() {
            @Override
            public void onResponse(Call<AuthResponse> call, Response<AuthResponse> resp) {
                btnLogin.setEnabled(true);

                if (!resp.isSuccessful()) {
                    String errorMessage = "Lỗi HTTP " + resp.code();
                    try {
                        // Cố gắng đọc thông báo lỗi từ body
                        if (resp.errorBody() != null) {
                            errorMessage += ": " + resp.errorBody().string();
                        }
                    } catch (IOException e) {
                        Log.e(TAG, "Error reading errorBody: ", e);
                    }
                    toast("Đăng nhập thất bại: Lỗi server " + resp.code());
                    Log.e(TAG, "API Login Error: " + errorMessage);
                    return;
                }

                AuthResponse data = resp.body();
                // ⭐ SỬA: Kiểm tra data có null không trước
                if (data == null) {
                    toast("Phản hồi server không hợp lệ.");
                    return;
                }

                // ⭐ Cải tiến: Kiểm tra thông báo thành công từ server
                if ("ok".equalsIgnoreCase(data.getMessage()) && data.getUser() != null) {
                    // Lưu session
                    SharedPreferences sp = getSharedPreferences("auth", MODE_PRIVATE);
                    sp.edit()
                            .putInt("customerID", data.getUser().getCustomerID())
                            .putString("fullName", data.getUser().getFullName())
                            .putString("email", data.getUser().getEmail())
                            .apply();

                    Log.i(TAG, "Login successful. User: " + data.getUser().getFullName() + ", ID: " + data.getUser().getCustomerID());

                    toast("Đăng nhập thành công!");
                    Intent intent = new Intent(LoginActivity.this, HomeActivity.class);
                    // Dùng cờ để xóa hết các màn hình cũ, tránh quay lại màn hình Login
                    intent.setFlags(Intent.FLAG_ACTIVITY_NEW_TASK | Intent.FLAG_ACTIVITY_CLEAR_TASK);
                    startActivity(intent);
                    finish();
                } else {
                    // Xử lý lỗi logic (message = "fail" hoặc không khớp)
                    toast("Sai email hoặc mật khẩu.");
                    Log.w(TAG, "Login logic failed. Message: " + data.getMessage());
                }
            }

            @Override
            public void onFailure(Call<AuthResponse> call, Throwable t) {
                btnLogin.setEnabled(true);
                toast("Không kết nối được server. Vui lòng kiểm tra mạng.");
                Log.e(TAG, "API Login Failure: " + t.getMessage(), t);
            }
        });
    }

    private void toast(String m){
        Toast.makeText(this, m, Toast.LENGTH_SHORT).show();
    }
}