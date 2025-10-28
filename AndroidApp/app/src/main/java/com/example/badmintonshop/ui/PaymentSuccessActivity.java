package com.example.badmintonshop.ui;

import android.content.Intent;
import android.os.Bundle;
import android.widget.Button;

import androidx.activity.OnBackPressedCallback;
import androidx.appcompat.app.AppCompatActivity;

import com.example.badmintonshop.R;

public class PaymentSuccessActivity extends AppCompatActivity {

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_payment_success); // Sẽ tạo ở Bước 3

        Button btnViewOrders = findViewById(R.id.btn_view_orders);
        Button btnContinueShopping = findViewById(R.id.btn_continue_shopping);

        // Nút xem đơn hàng
        btnViewOrders.setOnClickListener(v -> {
            Intent intent = new Intent(PaymentSuccessActivity.this, YourOrdersActivity.class);
            intent.addFlags(Intent.FLAG_ACTIVITY_CLEAR_TOP | Intent.FLAG_ACTIVITY_NEW_TASK);
            startActivity(intent);
            finish();
        });

        // Nút tiếp tục mua sắm
        btnContinueShopping.setOnClickListener(v -> {
            goToHome();
        });

        // Xử lý nút Back (nên quay về trang chủ)
        getOnBackPressedDispatcher().addCallback(this, new OnBackPressedCallback(true) {
            @Override
            public void handleOnBackPressed() {
                goToHome();
            }
        });
    }

    private void goToHome() {
        Intent intent = new Intent(PaymentSuccessActivity.this, HomeActivity.class);
        intent.addFlags(Intent.FLAG_ACTIVITY_CLEAR_TOP | Intent.FLAG_ACTIVITY_NEW_TASK);
        startActivity(intent);
        finish();
    }
}