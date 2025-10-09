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

import retrofit2.Call;
import retrofit2.Callback;
import retrofit2.Response;

public class LoginActivity extends AppCompatActivity {

    private static final String BASE_URL = "http://10.0.2.2/api/BadmintonShop/"; // đổi thành IP LAN nếu chạy máy thật

    private ApiService api;
    private TextInputEditText etEmail, etPassword;
    private MaterialButton btnLogin;
    private TextView tvRegister;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_login);

        api = ApiClient.get(BASE_URL).create(ApiService.class);

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
        String email = String.valueOf(etEmail.getText()).trim();
        String pass  = String.valueOf(etPassword.getText());

        if (email.isEmpty() || pass.isEmpty()) {
            toast("Nhập email và mật khẩu");
            return;
        }

        btnLogin.setEnabled(false);

        AuthLoginBody body = new AuthLoginBody();
        body.email = email;
        body.password = pass;

        api.login(body).enqueue(new Callback<AuthResponse>() {
            @Override public void onResponse(Call<AuthResponse> call, Response<AuthResponse> resp) {
                btnLogin.setEnabled(true);

                if (!resp.isSuccessful() || resp.body() == null) {
                    String err = "";
                    try { err = resp.errorBody() != null ? resp.errorBody().string() : ""; } catch (Exception ignored) {}
                    toast("Lỗi server: " + err);
                    Log.e("API", "errorBody=" + err);
                    return;
                }



                AuthResponse data = resp.body();
                if ("ok".equalsIgnoreCase(data.getMessage()) && data.getUser() != null) {
                    // Lưu session đơn giản
                    SharedPreferences sp = getSharedPreferences("auth", MODE_PRIVATE);
                    sp.edit()
                            .putInt("customerID", data.getUser().getCustomerID())
                            .putString("fullName", data.getUser().getFullName())
                            .putString("email", data.getUser().getEmail())
                            .apply();

                    toast("Đăng nhập thành công!");
                    startActivity(new Intent(LoginActivity.this, HomeActivity.class));
                    SharedPreferences sharedPreferences = getSharedPreferences("auth", MODE_PRIVATE);
                    sharedPreferences.edit()
                            .putInt("customerID", data.getUser().getCustomerID())
                            .putString("fullName", data.getUser().getFullName())
                            .putString("email", data.getUser().getEmail())
                            .apply();

                    finish();
                } else {
                    toast("Sai email hoặc mật khẩu");
                }
            }

            @Override public void onFailure(Call<AuthResponse> call, Throwable t) {
                btnLogin.setEnabled(true);
                toast("Không kết nối được server");
            }
        });
    }

    private void toast(String m){
        Toast.makeText(this, m, Toast.LENGTH_SHORT).show();
    }
}
