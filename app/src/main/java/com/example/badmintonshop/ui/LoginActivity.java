package com.example.badmintonshop.ui;

import android.content.Intent;
import android.content.SharedPreferences;
import android.os.Bundle;
import android.util.Log;
import android.widget.TextView;
import android.widget.Toast;
// ⭐ Import mới để xử lý Deep Link
import android.net.Uri;

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

    // ⭐ PHƯƠNG THỨC MỚI: Xử lý Deep Link khi Activity được mở hoặc quay lại
    @Override
    protected void onResume() {
        super.onResume();
        handleDeepLink(getIntent());
    }

    // ⭐ PHƯƠNG THỨC MỚI: Logic xử lý Deep Link
    private void handleDeepLink(Intent intent) {
        String action = intent.getAction();
        Uri data = intent.getData();

        if (Intent.ACTION_VIEW.equals(action) && data != null) {
            String scheme = data.getScheme(); // badmintonshop
            String host = data.getHost();     // verify
            String path = data.getPath();     // /success hoặc /failure

            // Chỉ xử lý nếu scheme và host khớp với Intent Filter
            if ("badmintonshop".equals(scheme) && "verify".equals(host)) {

                if ("/success".equals(path)) {
                    // ⭐ THÀNH CÔNG: Hiển thị thông báo và khuyến khích đăng nhập
                    toast("Kích hoạt tài khoản thành công! Bạn đã có thể đăng nhập.");
                    Log.i(TAG, "Deep Link: Verification SUCCESS.");

                } else if ("/failure".equals(path)) {
                    // ⭐ THẤT BẠI: Hiển thị lỗi
                    String error = data.getQueryParameter("error");
                    String msg = "Xác nhận thất bại. Vui lòng kiểm tra lại email hoặc đăng ký lại.";

                    if (error != null) {
                        if (error.equals("expired_or_used")) {
                            msg = "Liên kết đã hết hạn hoặc đã được sử dụng. Vui lòng đăng ký lại.";
                        } else if (error.equals("invalid_token")) {
                            msg = "Mã xác nhận không hợp lệ.";
                        }
                    }
                    toast(msg);
                    Log.w(TAG, "Deep Link: Verification FAILURE. Error: " + error);
                }

                // ⭐ QUAN TRỌNG: Xóa dữ liệu Intent để không gọi lại logic này lần nữa
                // khi người dùng chuyển hướng nội bộ.
                intent.setData(null);
                setIntent(new Intent());
            }
        }
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

                // ⭐ THAY ĐỔI LỚN: Xử lý lỗi HTTP 403 (Forbidden)
                if (!resp.isSuccessful()) {

                    if (resp.code() == 403) {
                        // Xử lý lỗi tài khoản chưa xác nhận (từ login.php đã sửa)
                        String errorBodyString = null;
                        try {
                            // Cố gắng đọc thông báo lỗi từ body
                            if (resp.errorBody() != null) {
                                errorBodyString = resp.errorBody().string();
                                Log.e(TAG, "Error 403 Body: " + errorBodyString);
                                // Dù không parse JSON, ta có thể log để debug
                            }
                        } catch (IOException e) {
                            Log.e(TAG, "Error reading errorBody: ", e);
                        }

                        // Hiển thị thông báo yêu cầu xác nhận email
                        toast("Đăng nhập thất bại: Tài khoản chưa được xác nhận email. Vui lòng kiểm tra hộp thư.");
                        return;
                    }

                    // Xử lý các lỗi HTTP khác (400, 401, 500...)
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

                // ⭐ Logic thành công: Đã được đảm bảo là đã xác nhận (isEmailVerified=1) do API 403 đã lọc
                if ("ok".equalsIgnoreCase(data.getMessage()) && data.getUser() != null) {

                    // Lưu session
                    SharedPreferences sp = getSharedPreferences("auth", MODE_PRIVATE);
                    sp.edit()
                            .putInt("customerID", data.getUser().getCustomerID())
                            .putString("fullName", data.getUser().getFullName())
                            .putString("email", data.getUser().getEmail())
                            // Thêm trạng thái xác nhận (giá trị 1 nếu thành công)
                            .putInt("isEmailVerified", data.getUser().getIsEmailVerified())
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
