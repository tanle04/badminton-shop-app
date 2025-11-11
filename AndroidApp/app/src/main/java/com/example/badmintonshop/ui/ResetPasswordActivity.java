package com.example.badmintonshop.ui;

import android.content.Intent;
import android.os.Bundle;
import android.util.Log;
import android.widget.ImageView;
import android.widget.TextView;
import android.widget.Toast;

import androidx.appcompat.app.AppCompatActivity;

import com.example.badmintonshop.R;
import com.example.badmintonshop.network.ApiClient;
import com.example.badmintonshop.network.ApiService;
import com.example.badmintonshop.network.dto.ApiResponse;
import com.google.android.material.button.MaterialButton;
import com.google.android.material.textfield.TextInputEditText;

import java.io.IOException;
import java.util.HashMap;
import java.util.Map;

import retrofit2.Call;
import retrofit2.Callback;
import retrofit2.Response;

public class ResetPasswordActivity extends AppCompatActivity {

    private static final String TAG = "ResetPassActivity";
    private ApiService api;
    private TextInputEditText etOtp, etNewPassword;
    private MaterialButton btnResetPassword;
    private ImageView btnBack;
    private TextView tvEmailInfo;

    private String userEmail; // Biến để lưu email từ Intent

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_reset_password);

        // Lấy email từ ForgotPasswordActivity
        userEmail = getIntent().getStringExtra("USER_EMAIL");
        if (userEmail == null || userEmail.isEmpty()) {
            toast("Lỗi: Không tìm thấy email người dùng.");
            finish();
            return;
        }

        api = ApiClient.getApiService();
        etOtp = findViewById(R.id.etOtp);
        etNewPassword = findViewById(R.id.etNewPassword);
        btnResetPassword = findViewById(R.id.btnResetPassword);
        btnBack = findViewById(R.id.btnBackReset);
        tvEmailInfo = findViewById(R.id.tvEmailInfo);

        // Hiển thị email cho người dùng
        tvEmailInfo.setText("Mã đã được gửi đến " + userEmail + ". Vui lòng nhập mã và mật khẩu mới.");

        btnBack.setOnClickListener(v -> finish());
        btnResetPassword.setOnClickListener(v -> resetPasswordRequest());
    }

    private void resetPasswordRequest() {
        String otp = etOtp.getText() != null ? etOtp.getText().toString().trim() : "";
        String newPassword = etNewPassword.getText() != null ? etNewPassword.getText().toString() : "";

        if (otp.length() != 6) {
            toast("Mã OTP phải có 6 chữ số.");
            return;
        }
        if (newPassword.length() < 6) {
            toast("Mật khẩu mới phải có ít nhất 6 ký tự.");
            return;
        }

        btnResetPassword.setEnabled(false);
        toast("Đang xử lý...");

        Map<String, String> body = new HashMap<>();
        body.put("email", userEmail);
        body.put("otp", otp);
        body.put("new_password", newPassword);

        api.resetPassword(body).enqueue(new Callback<ApiResponse>() {
            @Override
            public void onResponse(Call<ApiResponse> call, Response<ApiResponse> response) {
                btnResetPassword.setEnabled(true);

                if (response.isSuccessful() && response.body() != null && response.body().isSuccess()) {
                    // THÀNH CÔNG
                    toast(response.body().getMessage()); // "Đặt lại mật khẩu thành công!"

                    // Quay về màn hình Login
                    Intent intent = new Intent(ResetPasswordActivity.this, LoginActivity.class);
                    // Xóa tất cả màn hình (Forgot, Reset) khỏi stack và đưa Login lên đầu
                    intent.setFlags(Intent.FLAG_ACTIVITY_CLEAR_TOP | Intent.FLAG_ACTIVITY_SINGLE_TOP);
                    startActivity(intent);
                    finish(); // Đóng màn hình này
                } else {
                    // LỖI (OTP sai, hết hạn, v.v.)
                    String errorMessage = "Lỗi không xác định";
                    if (response.body() != null && response.body().getMessage() != null) {
                        errorMessage = response.body().getMessage();
                    } else if (response.errorBody() != null) {
                        try {
                            // Cố gắng đọc thông báo lỗi từ server
                            String errorStr = response.errorBody().string();
                            // Giả sử server trả về JSON lỗi: {"isSuccess":false, "message":"Lỗi..."}
                            // Thử phân tích cú pháp đơn giản, nếu không được thì hiển thị thô
                            if (errorStr.contains("\"message\"")) {
                                errorMessage = errorStr.split("\"message\":\"")[1].split("\"")[0];
                            } else {
                                errorMessage = errorStr;
                            }
                        } catch (Exception e) {
                            errorMessage = "Lỗi " + response.code();
                        }
                    } else {
                        errorMessage = "Lỗi " + response.code();
                    }
                    toast(errorMessage); // Hiển thị lỗi cho người dùng (ví dụ: "Mã OTP đã hết hạn.")
                    Log.w(TAG, "Reset failed: " + errorMessage);
                }
            }

            @Override
            public void onFailure(Call<ApiResponse> call, Throwable t) {
                btnResetPassword.setEnabled(true);
                toast("Lỗi mạng: " + t.getMessage());
                Log.e(TAG, "API failure: ", t);
            }
        });
    }

    private void toast(String m) {
        Toast.makeText(this, m, Toast.LENGTH_SHORT).show();
    }
}