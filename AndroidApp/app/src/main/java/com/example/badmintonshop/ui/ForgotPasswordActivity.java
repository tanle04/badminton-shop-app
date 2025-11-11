package com.example.badmintonshop.ui;

import android.content.Intent;
import android.os.Bundle;
import android.util.Log;
import android.widget.ImageView;
import android.widget.Toast;

import androidx.appcompat.app.AppCompatActivity;

import com.example.badmintonshop.R;
import com.example.badmintonshop.network.ApiClient;
import com.example.badmintonshop.network.ApiService;
import com.example.badmintonshop.network.dto.ApiResponse; // Dùng chung ApiResponse
import com.google.android.material.button.MaterialButton;
import com.google.android.material.textfield.TextInputEditText;

import java.io.IOException;
import java.util.HashMap;
import java.util.Map;

import retrofit2.Call;
import retrofit2.Callback;
import retrofit2.Response;

public class ForgotPasswordActivity extends AppCompatActivity {

    private static final String TAG = "ForgotPassActivity";
    private ApiService api;
    private TextInputEditText etEmailForgot;
    private MaterialButton btnSendOtp;
    private ImageView btnBack;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_forgot_password);

        api = ApiClient.getApiService();
        etEmailForgot = findViewById(R.id.etEmailForgot);
        btnSendOtp = findViewById(R.id.btnSendOtp);
        btnBack = findViewById(R.id.btnBack);

        btnBack.setOnClickListener(v -> finish());
        btnSendOtp.setOnClickListener(v -> sendOtpRequest());
    }

    private void sendOtpRequest() {
        String email = etEmailForgot.getText() != null ? etEmailForgot.getText().toString().trim() : "";

        if (email.isEmpty() || !android.util.Patterns.EMAIL_ADDRESS.matcher(email).matches()) {
            toast("Vui lòng nhập email hợp lệ.");
            return;
        }

        btnSendOtp.setEnabled(false);
        toast("Đang gửi yêu cầu...");

        Map<String, String> body = new HashMap<>();
        body.put("email", email);

        api.requestPasswordOtp(body).enqueue(new Callback<ApiResponse>() {
            @Override
            public void onResponse(Call<ApiResponse> call, Response<ApiResponse> response) {
                btnSendOtp.setEnabled(true);

                if (response.isSuccessful() && response.body() != null && response.body().isSuccess()) {
                    // THÀNH CÔNG: Email tồn tại, PHP đã gửi OTP
                    toast(response.body().getMessage());

                    // Chuyển sang màn hình Reset (Giai đoạn 2)
                    Intent intent = new Intent(ForgotPasswordActivity.this, ResetPasswordActivity.class);
                    intent.putExtra("USER_EMAIL", email);
                    startActivity(intent);
                    finish();
                } else {
                    // ⭐ THAY ĐỔI CHÍNH Ở ĐÂY: Xử lý lỗi (404, 400, 500)
                    String errorMsg = "Lỗi không xác định";

                    if(response.errorBody() != null) {
                        try {
                            // Đọc nội dung lỗi từ server
                            String errorStr = response.errorBody().string();
                            Log.e(TAG, "API Error Body: " + errorStr);

                            // Thử phân tích cú pháp JSON đơn giản để lấy 'message'
                            // (Giống cách ResetPasswordActivity đang làm)
                            if (errorStr.contains("\"message\"")) {
                                errorMsg = errorStr.split("\"message\":\"")[1].split("\"")[0];
                            } else {
                                errorMsg = "Lỗi " + response.code(); // Fallback
                            }
                        } catch (Exception e) {
                            Log.e(TAG, "Error reading/parsing errorBody", e);
                            errorMsg = "Lỗi " + response.code(); // Fallback
                        }
                    } else if (response.body() != null && response.body().getMessage() != null) {
                        // Trường hợp hiếm: Server trả 200 nhưng isSuccess = false
                        errorMsg = response.body().getMessage();
                    } else {
                        errorMsg = "Lỗi " + response.code();
                    }

                    // Hiển thị lỗi (ví dụ: "Email không tồn tại trong hệ thống.")
                    toast(errorMsg);
                    Log.e(TAG, "API request failed: " + response.code() + " | " + errorMsg);

                    // Quan trọng: KHÔNG chuyển activity, ở lại màn hình này
                }
            }

            @Override
            public void onFailure(Call<ApiResponse> call, Throwable t) {
                btnSendOtp.setEnabled(true);
                toast("Lỗi mạng: " + t.getMessage());
                Log.e(TAG, "API failure: ", t);
            }
        });
    }

    private void toast(String m) {
        Toast.makeText(this, m, Toast.LENGTH_SHORT).show();
    }
}
