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

    // ⭐ URL này được lấy chính xác từ file vnpay_helper.php của bạn
    // Cần đảm bảo đây là đường dẫn ngrok-free.dev chính xác mà bạn đang dùng.
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
        OnBackPressedCallback callback = new OnBackPressedCallback(true /* Bật mặc định */) {
            @Override
            public void handleOnBackPressed() {
                if (webView.canGoBack()) {
                    webView.goBack();
                } else {
                    // Nếu không thể quay lại trong WebView, báo hủy và đóng Activity
                    Log.d(TAG, "Back pressed. Setting result CANCELED.");
                    setResult(RESULT_CANCELED);
                    // Dùng finish() thay vì gọi lại onBackPressed()
                    finish();
                }
            }
        };
        getOnBackPressedDispatcher().addCallback(this, callback);
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

            // ⭐ BẮT URL SERVER TRẢ VỀ (ví dụ: https://slimiest-unmisgivingly-abdul.ngrok-free.dev/api/BadmintonShop/payments/vnpay_return.php?...)
            if (url.startsWith(VNPAY_RETURN_URL)) {
                Log.i(TAG, "Intercepted VNPAY Return URL: " + url);

                Uri uri = Uri.parse(url);
                String responseCode = uri.getQueryParameter("vnp_ResponseCode");

                if ("00".equals(responseCode)) {
                    Log.i(TAG, "Payment Success (Code 00).");
                    setResult(RESULT_OK); // Trả về RESULT_OK
                } else {
                    Log.w(TAG, "Payment Failed or Cancelled (Code: " + responseCode + ")");
                    setResult(RESULT_CANCELED);
                }

                // Chú ý: Script PHP trên server sẽ redirect lần cuối
                // sang 'badmintonshop://yourorders?status=success&orderID=96'.
                // Nhưng vì script PHP được gọi đồng bộ, khi URL VNPAY_RETURN_URL được gọi
                // và trả về, mọi thứ trên server đã xong. Chỉ cần trả về RESULT_OK là đủ.
                finish();
                return true;
            }

            // ⭐ BẮT URL DEEP LINK (badmintonshop://yourorders?...)
            // Cần bắt URL Deep Link để kích hoạt làm mới, nếu PaymentActivity không phải là nơi cuối cùng.
            if (url.startsWith("badmintonshop://")) {
                Log.i(TAG, "Intercepted Deep Link URL: " + url);

                // Mặc dù Deep Link có thể tự mở ứng dụng, việc xử lý nó trong WebView
                // giúp chúng ta lấy thông tin status/orderID chính xác nhất.

                Uri uri = Uri.parse(url);
                String status = uri.getQueryParameter("status");

                // Trả về RESULT_OK nếu status là 'success' hoặc 'already_processed_processing'
                if (status != null && status.startsWith("success")) {
                    setResult(RESULT_OK);
                } else {
                    setResult(RESULT_CANCELED);
                }

                finish();
                return true;
            }

            return false;
        }
    }
}