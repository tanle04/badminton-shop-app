package com.example.badmintonshop.ui;

import android.content.Intent;
import android.os.Bundle;
import android.widget.Button;
import androidx.activity.OnBackPressedCallback;
import androidx.appcompat.app.AppCompatActivity;

import com.example.badmintonshop.R;

public class PaymentFailedActivity extends AppCompatActivity {

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_payment_failed); // Sẽ tạo ở Bước 5

        Button btnTryAgain = findViewById(R.id.btn_try_again);
        Button btnBackToHome = findViewById(R.id.btn_back_to_home);

        // Nút Thử lại
        btnTryAgain.setOnClickListener(v -> {
            // Đóng Activity này, người dùng sẽ quay lại màn hình Checkout
            finish();
        });

        // Nút Quay về trang chủ
        btnBackToHome.setOnClickListener(v -> {
            goToHome();
        });

        // Xử lý nút Back (quay lại Checkout)
        getOnBackPressedDispatcher().addCallback(this, new OnBackPressedCallback(true) {
            @Override
            public void handleOnBackPressed() {
                // Giống như bấm "Thử lại"
                finish();
            }
        });
    }

    private void goToHome() {
        Intent intent = new Intent(PaymentFailedActivity.this, HomeActivity.class);
        intent.addFlags(Intent.FLAG_ACTIVITY_CLEAR_TOP | Intent.FLAG_ACTIVITY_NEW_TASK);
        startActivity(intent);
        finish();
    }
}