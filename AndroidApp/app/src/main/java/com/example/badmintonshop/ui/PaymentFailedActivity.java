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
        }


        Button btnTryAgain = findViewById(R.id.btn_try_again);
        Button btnBackToHome = findViewById(R.id.btn_back_to_home);

        // ⭐ Nút Thử lại: Kích hoạt lại luồng thanh toán (Repay)
        btnTryAgain.setOnClickListener(v -> {
            if (orderId != -1) {
                Intent resultIntent = new Intent();
                // ⭐ TRUYỀN ORDER ID DƯỚI KEY "RETRY_ORDER_ID"
                resultIntent.putExtra("RETRY_ORDER_ID", orderId);
                setResult(RESULT_OK, resultIntent); // Kích hoạt failureLauncher.onResult(RESULT_OK)
                finish();
            } else {
                // Nếu ID bị mất, trả về CANCELED
                setResult(RESULT_CANCELED);
                finish();
            }
        });

        // Nút Quay về trang chủ
        btnBackToHome.setOnClickListener(v -> {
            goToHome();
        });

        // Xử lý nút Back (quay lại Activity cha YourOrdersActivity)
        getOnBackPressedDispatcher().addCallback(this, new OnBackPressedCallback(true) {
            @Override
            public void handleOnBackPressed() {
                // Trả về RESULT_CANCELED (không Repay)
                Intent resultIntent = new Intent();
                setResult(RESULT_CANCELED, resultIntent);
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