package com.example.badmintonshop.ui;

import android.content.Intent;
import android.os.Bundle;
import android.util.Log;
import android.widget.Button;
import androidx.activity.OnBackPressedCallback;
import androidx.appcompat.app.AppCompatActivity;

import com.example.badmintonshop.R;

public class PaymentFailedActivity extends AppCompatActivity {

    private int orderId = -1;
    private static final String TAG = "PaymentFailedActivity";

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_payment_failed);

        // ⭐ Lấy OrderID từ Intent
        orderId = getIntent().getIntExtra("ORDER_ID", -1);
        if (orderId == -1) {
            Log.e(TAG, "ORDER_ID not found in Intent.");
        } else {
            Log.d(TAG, "PaymentFailedActivity opened for OrderID: " + orderId);
        }

        Button btnTryAgain = findViewById(R.id.btn_try_again);
        Button btnBackToHome = findViewById(R.id.btn_back_to_home);

        // ⭐ NÚT "THỬ LẠI": Trả về RESULT_OK với key "RETRY_ORDER_ID"
        btnTryAgain.setOnClickListener(v -> {
            if (orderId != -1) {
                Log.d(TAG, "User clicked Try Again. Sending RETRY_ORDER_ID: " + orderId);
                Intent resultIntent = new Intent();
                resultIntent.putExtra("RETRY_ORDER_ID", orderId); // ⭐ KEY quan trọng
                setResult(RESULT_OK, resultIntent); // Kích hoạt failureLauncher trong OrderFragment
                finish();
            } else {
                Log.e(TAG, "Cannot retry: OrderID is -1");
                setResult(RESULT_CANCELED);
                finish();
            }
        });

        // Nút "Quay về trang chủ"
        btnBackToHome.setOnClickListener(v -> {
            goToHome();
        });

        // Xử lý nút Back (hệ thống)
        getOnBackPressedDispatcher().addCallback(this, new OnBackPressedCallback(true) {
            @Override
            public void handleOnBackPressed() {
                Log.d(TAG, "Back button pressed. Returning CANCELED.");
                setResult(RESULT_CANCELED);
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