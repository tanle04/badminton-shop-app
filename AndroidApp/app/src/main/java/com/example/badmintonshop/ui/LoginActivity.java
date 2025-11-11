package com.example.badmintonshop.ui;

import android.content.Intent;
import android.content.SharedPreferences;
import android.os.Bundle;
import android.util.Log;
import android.widget.TextView;
import android.widget.Toast;
import android.net.Uri; // Import mới để xử lý Deep Link

import androidx.appcompat.app.AppCompatActivity;

import com.example.badmintonshop.R;
import com.example.badmintonshop.network.ApiClient;
import com.example.badmintonshop.network.ApiService;
import com.example.badmintonshop.network.dto.AuthLoginBody;
import com.example.badmintonshop.network.dto.AuthResponse;
import com.google.android.material.button.MaterialButton;
import com.google.android.material.textfield.TextInputEditText;

// ⭐ THÊM IMPORT ĐỂ PHÂN TÍCH JSON
import org.json.JSONObject;

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
    private TextView tvForgotPassword;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_login);

        api = ApiClient.getApiService();

        etEmail = findViewById(R.id.etEmail);
        etPassword = findViewById(R.id.etPassword);
        btnLogin = findViewById(R.id.btnLogin);
        tvRegister = findViewById(R.id.tvRegister);
        tvForgotPassword = findViewById(R.id.tvForgotPassword);

        btnLogin.setOnClickListener(v -> doLogin());
        tvRegister.setOnClickListener(v ->
                startActivity(new Intent(this, RegisterActivity.class))
        );

        tvForgotPassword.setOnClickListener(v ->
                startActivity(new Intent(this, ForgotPasswordActivity.class))
        );

        handleDeepLink(getIntent());
    }

    @Override
    protected void onNewIntent(Intent intent) {
        super.onNewIntent(intent);
        setIntent(intent);
        handleDeepLink(intent);
    }

    private void handleDeepLink(Intent intent) {
        // ... (Logic xử lý Deep Link của bạn đã chính xác, giữ nguyên) ...
        String action = intent.getAction();
        Uri data = intent.getData();

        if (Intent.ACTION_VIEW.equals(action) && data != null) {
            String scheme = data.getScheme();
            String host = data.getHost();
            String path = data.getPath();

            if ("badmintonshop".equals(scheme) && "verify".equals(host)) {
                if ("/success".equals(path)) {
                    toast("Kích hoạt tài khoản thành công! Bạn đã có thể đăng nhập.");
                    Log.i(TAG, "Deep Link: Verification SUCCESS.");
                } else if ("/failure".equals(path)) {
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
                intent.setData(null);
                setIntent(new Intent());
            }
        }
    }

    private void doLogin() {
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

                // ⭐ BẮT ĐẦU SỬA LỖI ⭐
                // Xử lý các phản hồi KHÔNG THÀNH CÔNG (HTTP Status != 2xx)
                if (!resp.isSuccessful()) {
                    // Thử phân tích JSON từ errorBody
                    String serverMessage = null; // Tin nhắn logic (ví dụ: 'unverified_email')
                    String displayError = null;  // Tin nhắn hiển thị (ví dụ: 'Tài khoản chưa xác nhận...')

                    if (resp.errorBody() != null) {
                        try {
                            // Đọc nội dung lỗi 1 LẦN DUY NHẤT
                            String errorJson = resp.errorBody().string();
                            Log.w(TAG, "API Error Body: " + errorJson);

                            // Phân tích JSON thủ công
                            JSONObject jsonObject = new JSONObject(errorJson);

                            // Lấy 'message' (dùng cho logic)
                            if (jsonObject.has("message")) {
                                serverMessage = jsonObject.getString("message");
                            }

                            // Lấy 'error' (dùng để hiển thị)
                            if (jsonObject.has("error")) {
                                displayError = jsonObject.getString("error");
                            }
                        } catch (Exception e) {
                            Log.e(TAG, "Error parsing errorBody: ", e);
                        }
                    }

                    // Bây giờ, xử lý lỗi dựa trên mã HTTP và tin nhắn JSON
                    // Các case này khớp với các mục trong checklist của bạn
                    switch (resp.code()) {
                        case 401: // [Checklist] Đăng nhập lỗi: Sai mật khẩu / Email không tồn tại
                            // API trả về: { "isSuccess": false, "message": "Sai email hoặc mật khẩu." }
                            toast(serverMessage != null ? serverMessage : "Sai email hoặc mật khẩu.");
                            break;

                        case 403: // [Checklist] Bị cấm (chưa xác nhận HOẶC bị khóa)
                            if ("unverified_email".equals(serverMessage)) {
                                // [Checklist] Đăng nhập lỗi: Tài khoản chưa xác thực
                                // API trả về: { "isSuccess": false, "message": "unverified_email", "error": "Tài khoản chưa được..." }
                                toast(displayError != null ? displayError : "Tài khoản chưa được xác nhận email.");
                            } else if ("account_locked".equals(serverMessage)) {
                                // [Checklist] Đăng nhập lỗi: Tài khoản bị khóa (is_active = 0)
                                // API trả về: { "isSuccess": false, "message": "account_locked", "error": "Tài khoản này đã bị khóa..." }
                                toast(displayError != null ? displayError : "Tài khoản này đã bị khóa.");
                            } else {
                                // Lỗi 403 chung
                                toast("Không có quyền truy cập (403).");
                            }
                            break;

                        case 400: // [Checklist] Dữ liệu gửi lên không hợp lệ (từ phía client)
                            // API trả về: { "isSuccess": false, "message": "Email hoặc mật khẩu không hợp lệ." }
                            toast(serverMessage != null ? serverMessage : "Email hoặc mật khẩu không hợp lệ.");
                            break;

                        default: // Lỗi 500 hoặc lỗi không xác định khác
                            toast("Lỗi server " + resp.code() + ". Vui lòng thử lại sau.");
                            Log.e(TAG, "Unhandled API Error: " + resp.code());
                            break;
                    }
                    // ⭐ KẾT THÚC SỬA LỖI ⭐
                    return; // Quan trọng: Dừng thực thi tại đây
                }

                // Xử lý khi PHẢN HỒI THÀNH CÔNG (HTTP 200)
                AuthResponse data = resp.body();
                if (data == null) {
                    toast("Phản hồi server không hợp lệ.");
                    return;
                }

                // [Checklist] Đăng nhập thành công (email, mật khẩu đúng)
                if ("ok".equalsIgnoreCase(data.getMessage()) && data.getUser() != null) {
                    SharedPreferences sp = getSharedPreferences("auth", MODE_PRIVATE);
                    sp.edit()
                            .putInt("customerID", data.getUser().getCustomerID())
                            .putString("fullName", data.getUser().getFullName())
                            .putString("email", data.getUser().getEmail())
                            .putInt("isEmailVerified", data.getUser().getIsEmailVerified())
                            .apply();

                    Log.i(TAG, "Login successful. User: " + data.getUser().getFullName() + ", ID: " + data.getUser().getCustomerID());

                    toast("Đăng nhập thành công!");
                    Intent intent = new Intent(LoginActivity.this, HomeActivity.class);
                    intent.setFlags(Intent.FLAG_ACTIVITY_NEW_TASK | Intent.FLAG_ACTIVITY_CLEAR_TASK);
                    startActivity(intent);
                    finish();
                } else {
                    // Xử lý lỗi logic 200 OK (ví dụ: API trả về 200 nhưng message="fail")
                    // API hiện tại của bạn không có trường hợp này, nhưng đây là một dự phòng tốt.
                    toast(data.getMessage() != null ? data.getMessage() : "Lỗi logic không xác định.");
                    Log.w(TAG, "Login logic failed despite 200 OK. Message: " + data.getMessage());
                }
            }

            @Override
            public void onFailure(Call<AuthResponse> call, Throwable t) {
                // Lỗi mạng (không kết nối được)
                btnLogin.setEnabled(true);
                toast("Không kết nối được server. Vui lòng kiểm tra mạng.");
                Log.e(TAG, "API Login Failure: " + t.getMessage(), t);
            }
        });
    }

    private void toast(String m){
        // ⭐ Cải tiến: Đảm bảo Toast chạy trên UI Thread (an toàn cho callbacks)
        runOnUiThread(() -> {
            Toast.makeText(getApplicationContext(), m, Toast.LENGTH_SHORT).show();
        });
    }
}