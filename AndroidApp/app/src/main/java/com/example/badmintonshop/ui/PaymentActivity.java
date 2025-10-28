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

// THÊM IMPORT NÀY
import androidx.activity.OnBackPressedCallback;
import androidx.appcompat.app.AppCompatActivity;
import androidx.appcompat.widget.Toolbar;

import com.example.badmintonshop.R;

public class PaymentActivity extends AppCompatActivity {

    // ⭐ URL này được lấy chính xác từ file vnpay_helper.php của bạn
    private static final String VNPAY_RETURN_URL = "https://slimiest-unmisgivingly-abdul.ngrok-free.dev/api/BadmintonShop/payments/vnpay_return.php";

    private static final String TAG = "PaymentActivity";

    private WebView webView;
    private ProgressBar progressBar;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_payment);

        Toolbar toolbar = findViewById(R.id.toolbar_payment);
        toolbar.setTitle("Thanh toán an toàn");
        toolbar.setNavigationOnClickListener(v -> {
            setResult(RESULT_CANCELED); // Báo là hủy
            finish();
        });

        webView = findViewById(R.id.payment_webview);
        progressBar = findViewById(R.id.payment_progress_bar);

        String vnpayUrl = getIntent().getStringExtra("VNPAY_URL");

        if (vnpayUrl == null || vnpayUrl.isEmpty()) {
            Log.e(TAG, "No VNPAY_URL provided in Intent.");
            Toast.makeText(this, "Lỗi: Không tìm thấy URL thanh toán.", Toast.LENGTH_SHORT).show();
            setResult(RESULT_CANCELED);
            finish();
            return;
        }

        webView.getSettings().setJavaScriptEnabled(true);
        webView.setWebViewClient(new MyWebViewClient());
        webView.loadUrl(vnpayUrl);

        // ⭐ SỬA LỖI DEPRECATED: Xử lý nút Back theo cách mới
        // Tạo một callback mới để xử lý nút back
        OnBackPressedCallback callback = new OnBackPressedCallback(true /* Bật mặc định */) {
            @Override
            public void handleOnBackPressed() {
                // Đây là nơi đặt logic cũ của bạn
                if (webView.canGoBack()) {
                    webView.goBack();
                } else {
                    // Nếu không thể quay lại trong WebView,
                    // hãy báo hủy và đóng Activity
                    Log.d(TAG, "Back pressed. Setting result CANCELED.");
                    setResult(RESULT_CANCELED);

                    // Vô hiệu hóa callback này
                    setEnabled(false);
                    // Và gọi lại hành động back mặc định (sẽ đóng Activity)
                    getOnBackPressedDispatcher().onBackPressed();
                }
            }
        };
        // Đăng ký callback này với dispatcher
        getOnBackPressedDispatcher().addCallback(this, callback);
    }

    private class MyWebViewClient extends WebViewClient {
        // ... (Code của MyWebViewClient giữ nguyên)
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

            if (url.startsWith(VNPAY_RETURN_URL)) {
                Log.i(TAG, "Intercepted VNPAY Return URL: " + url);

                Uri uri = Uri.parse(url);
                String responseCode = uri.getQueryParameter("vnp_ResponseCode");

                if ("00".equals(responseCode)) {
                    Log.i(TAG, "Payment Success (Code 00).");
                    setResult(RESULT_OK);
                } else {
                    Log.w(TAG, "Payment Failed or Cancelled (Code: " + responseCode + ")");
                    setResult(RESULT_CANCELED);
                }

                finish();
                return true;
            }
            return false;
        }
    }

    // ⭐ BẠN PHẢI XÓA HÀM CŨ NÀY ĐI
    /*
    @Override
    public void onBackPressed() {
        if (webView.canGoBack()) {
            webView.goBack();
        } else {
            Log.d(TAG, "Back pressed. Setting result CANCELED.");
            setResult(RESULT_CANCELED);
            super.onBackPressed();
        }
    }
    */
}