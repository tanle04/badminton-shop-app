package com.example.badmintonshop.ui;

import android.content.Intent;
import android.graphics.Bitmap;
import android.net.Uri;
import android.os.Bundle;
import android.view.View;
import android.webkit.WebView;
import android.webkit.WebViewClient;
import android.widget.ProgressBar;
import android.util.Log;
import android.widget.Toast;

import androidx.activity.OnBackPressedCallback;
import androidx.appcompat.app.AppCompatActivity;
import androidx.appcompat.widget.Toolbar;

import com.example.badmintonshop.R;

public class PaymentActivity extends AppCompatActivity {

    private static final String VNPAY_RETURN_URL = "https://slimiest-unmisgivingly-abdul.ngrok-free.dev/api/BadmintonShop/payments/vnpay_return.php";
    private static final String TAG = "PaymentActivity";

    private WebView webView;
    private ProgressBar progressBar;
    private int orderIdFromIntent = -1; // ⭐ Lưu OrderID từ Intent

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_payment);

        Toolbar toolbar = findViewById(R.id.toolbar_payment);
        toolbar.setTitle("Thanh toán an toàn");
        toolbar.setNavigationOnClickListener(v -> {
            returnResultAndFinish(RESULT_CANCELED); // ⭐ Hủy và trả OrderID
        });

        webView = findViewById(R.id.payment_webview);
        progressBar = findViewById(R.id.payment_progress_bar);

        String vnpayUrl = getIntent().getStringExtra("VNPAY_URL");
        // ⭐ LẤY ORDER ID TỪ INTENT (từ CheckoutActivity hoặc OrderFragment)
        String orderIdStr = getIntent().getStringExtra("ORDER_ID_RET");
        if (orderIdStr != null && !orderIdStr.isEmpty()) {
            try {
                orderIdFromIntent = Integer.parseInt(orderIdStr);
                Log.d(TAG, "OrderID from Intent: " + orderIdFromIntent);
            } catch (NumberFormatException e) {
                Log.e(TAG, "Invalid ORDER_ID_RET format: " + orderIdStr);
            }
        }

        if (vnpayUrl == null || vnpayUrl.isEmpty()) {
            Log.e(TAG, "No VNPAY_URL provided in Intent.");
            Toast.makeText(this, "Lỗi: Không tìm thấy URL thanh toán.", Toast.LENGTH_SHORT).show();
            returnResultAndFinish(RESULT_CANCELED);
            return;
        }

        webView.getSettings().setJavaScriptEnabled(true);
        webView.setWebViewClient(new MyWebViewClient());
        webView.loadUrl(vnpayUrl);

        // Xử lý nút Back
        OnBackPressedCallback callback = new OnBackPressedCallback(true) {
            @Override
            public void handleOnBackPressed() {
                if (webView.canGoBack()) {
                    webView.goBack();
                } else {
                    Log.d(TAG, "Back pressed. Setting result CANCELED.");
                    returnResultAndFinish(RESULT_CANCELED);
                }
            }
        };
        getOnBackPressedDispatcher().addCallback(this, callback);
    }

    // ⭐ HÀM TRẢ KẾT QUẢ VỚI ORDER_ID
    private void returnResultAndFinish(int resultCode) {
        Intent resultIntent = new Intent();
        if (orderIdFromIntent != -1) {
            resultIntent.putExtra("ORDER_ID", String.valueOf(orderIdFromIntent));
            Log.d(TAG, "Returning result " + resultCode + " with OrderID: " + orderIdFromIntent);
        }
        setResult(resultCode, resultIntent);
        finish();
    }

    private class MyWebViewClient extends WebViewClient {

        @Override
        public void onPageStarted(WebView view, String url, Bitmap favicon) {
            super.onPageStarted(view, url, favicon);
            progressBar.setVisibility(View.VISIBLE);
            Log.d(TAG, "Page started loading: " + url);
        }

        @Override
        public void onPageFinished(WebView view, String url) {
            super.onPageFinished(view, url);
            progressBar.setVisibility(View.GONE);
            Log.d(TAG, "Page finished loading: " + url);
        }

        @Override
        public boolean shouldOverrideUrlLoading(WebView view, String url) {
            Log.d(TAG, "URL Override attempt: " + url);

            // ⭐ BẮT URL SERVER (https://...vnpay_return.php?...)
            if (url.startsWith(VNPAY_RETURN_URL)) {
                Log.i(TAG, "Intercepted VNPAY Return URL: " + url);

                Uri uri = Uri.parse(url);
                String responseCode = uri.getQueryParameter("vnp_ResponseCode");

                if ("00".equals(responseCode)) {
                    Log.i(TAG, "Payment Success (Code 00).");
                    returnResultAndFinish(RESULT_OK); // ⭐ Trả về OK với OrderID
                } else {
                    Log.w(TAG, "Payment Failed or Cancelled (Code: " + responseCode + ")");
                    returnResultAndFinish(RESULT_CANCELED);
                }
                return true;
            }

            // ⭐ BẮT DEEP LINK (badmintonshop://yourorders?...)
            if (url.startsWith("badmintonshop://")) {
                Log.i(TAG, "Intercepted Deep Link URL: " + url);

                Uri uri = Uri.parse(url);
                String status = uri.getQueryParameter("status");

                // Trả về RESULT_OK nếu thành công
                if (status != null && status.startsWith("success")) {
                    returnResultAndFinish(RESULT_OK);
                } else {
                    returnResultAndFinish(RESULT_CANCELED);
                }
                return true;
            }

            return false;
        }
    }
}