package com.example.badmintonshop.ui;

import android.content.Intent;
import android.content.SharedPreferences;
import android.net.Uri;
import android.os.Bundle;
import android.util.Log;
import android.view.MenuItem;
import android.widget.Toast;

import androidx.activity.result.ActivityResultLauncher;
import androidx.activity.result.contract.ActivityResultContracts;
import androidx.annotation.NonNull;
import androidx.appcompat.app.AppCompatActivity;
import androidx.fragment.app.Fragment;
import androidx.viewpager2.widget.ViewPager2;

import com.example.badmintonshop.R;
import com.example.badmintonshop.adapter.OrderAdapter;
import com.example.badmintonshop.adapter.OrderPagerAdapter;
import com.example.badmintonshop.network.ApiClient;
import com.example.badmintonshop.network.ApiService;
import com.example.badmintonshop.network.dto.OrderDto;
import com.google.android.material.tabs.TabLayout;
import com.google.android.material.tabs.TabLayoutMediator;
import com.google.android.material.appbar.MaterialToolbar;

public class YourOrdersActivity extends AppCompatActivity implements OrderAdapter.OrderAdapterListener {

    private ViewPager2 viewPagerOrders;
    private TabLayout tabLayoutOrders;
    private MaterialToolbar toolbar;
    private ApiService apiService;

    // Launcher cho Reviews
    private ActivityResultLauncher<Intent> reviewActivityResultLauncher;

    // ⭐ ĐÃ SỬA: Launcher mới để mở OrderDetailActivity và bắt kết quả HỦY ĐƠN HÀNG
    private ActivityResultLauncher<Intent> orderDetailActivityResultLauncher;

    private final String[] tabTitles = {"All orders", "Processing", "Shipped", "Delivered", "Cancelled", "Refunded"};

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_your_orders);

        apiService = ApiClient.getApiService();

        toolbar = findViewById(R.id.toolbar);
        viewPagerOrders = findViewById(R.id.view_pager_orders);
        tabLayoutOrders = findViewById(R.id.tab_layout_orders);

        // Khởi tạo Activity Result Launcher cho Reviews
        reviewActivityResultLauncher = registerForActivityResult(
                new ActivityResultContracts.StartActivityForResult(),
                result -> {
                    if (result.getResultCode() == RESULT_OK) {
                        Toast.makeText(this, "Đơn hàng đã được đánh giá, đang cập nhật...", Toast.LENGTH_SHORT).show();
                        refreshCurrentOrderFragment();
                    }
                }
        );

        // ⭐ KHỞI TẠO LAUNCHER CHO ORDER DETAIL (HỦY ĐƠN HÀNG) ⭐
        orderDetailActivityResultLauncher = registerForActivityResult(
                new ActivityResultContracts.StartActivityForResult(),
                result -> {
                    if (result.getResultCode() == RESULT_OK) {
                        // Nếu OrderDetailActivity trả về RESULT_OK (sau khi Hủy thành công)
                        Toast.makeText(this, "Trạng thái đơn hàng đã được cập nhật.", Toast.LENGTH_SHORT).show();
                        // Chuyển về tab "All orders" hoặc "Cancelled"
                        viewPagerOrders.setCurrentItem(0, true);
                        refreshCurrentOrderFragment();
                    }
                }
        );
        // ⭐ KẾT THÚC LAUNCHER ⭐


        setSupportActionBar(toolbar);
        if (getSupportActionBar() != null) {
            getSupportActionBar().setTitle("Đơn hàng của bạn");
            getSupportActionBar().setDisplayHomeAsUpEnabled(true);
        }

        setupViewPagerAndTabs();

        // XỬ LÝ DEEP LINK TỪ VNPAY CALLBACK KHI ACTIVITY ĐƯỢC TẠO
        handleIntent(getIntent());
    }

    // Xử lý Deep Link khi Activity đã chạy (từ callback VNPay)
    @Override
    protected void onNewIntent(Intent intent) {
        super.onNewIntent(intent);
        setIntent(intent);
        handleIntent(intent);
    }

    // HÀM XỬ LÝ INTENT ĐỂ BẮT VÀ THÔNG BÁO KẾT QUẢ VNPAY
    private void handleIntent(Intent intent) {
        String action = intent.getAction();
        Uri data = intent.getData();

        if (Intent.ACTION_VIEW.equals(action) && data != null && "yourorders".equals(data.getHost())) {
            String status = data.getQueryParameter("status");
            String orderId = data.getQueryParameter("orderID");

            Log.d("VNPAY_DEEPLINK", "Status: " + status + ", OrderID: " + orderId);

            if ("success".equals(status)) {
                Toast.makeText(this, "Thanh toán thành công! Đơn hàng #" + orderId + " đang được xử lý.", Toast.LENGTH_LONG).show();
                viewPagerOrders.setCurrentItem(1, true); // Chuyển đến tab Processing
            } else if ("failed".equals(status) || "failed_cancelled".equals(status)) {
                Toast.makeText(this, "Thanh toán thất bại cho đơn hàng #" + orderId + " và đã bị hủy.", Toast.LENGTH_LONG).show();
            } else if ("security_error".equals(status)) {
                Toast.makeText(this, "Lỗi bảo mật/dữ liệu trong giao dịch VNPay.", Toast.LENGTH_LONG).show();
            }
            refreshCurrentOrderFragment();
        }
    }

    @Override
    public boolean onOptionsItemSelected(@NonNull MenuItem item) {
        if (item.getItemId() == android.R.id.home) {
            finish();
            return true;
        }
        return super.onOptionsItemSelected(item);
    }

    public int getCurrentCustomerId() {
        SharedPreferences sp = getSharedPreferences("auth", MODE_PRIVATE);
        return sp.getInt("customerID", -1);
    }

    private void setupViewPagerAndTabs() {
        OrderPagerAdapter adapter = new OrderPagerAdapter(this, tabTitles);
        viewPagerOrders.setAdapter(adapter);

        new TabLayoutMediator(tabLayoutOrders, viewPagerOrders, (tab, position) -> {
            tab.setText(tabTitles[position]);
        }).attach();

        viewPagerOrders.setCurrentItem(1, false);
    }

    // HÀM REFRESH DỮ LIỆU TRONG FRAGMENT HIỆN TẠI
    private void refreshCurrentOrderFragment() {
        Fragment fragment = getSupportFragmentManager()
                .findFragmentByTag("f" + viewPagerOrders.getCurrentItem());

        if (fragment instanceof OrderFragment) {
            ((OrderFragment) fragment).fetchOrders();
        }
    }


    // -----------------------------------------------------------------
    // TRIỂN KHAI CÁC PHƯƠNG THỨC CỦA OrderAdapter.OrderAdapterListener
    // -----------------------------------------------------------------

    // ⭐ ĐÃ SỬA: Xử lý click vào toàn bộ đơn hàng để mở OrderDetailActivity DÙNG LAUNCHER
    @Override
    public void onOrderClicked(OrderDto order) {
        Intent intent = new Intent(this, OrderDetailActivity.class);

        intent.putExtra("ORDER_DETAIL_DATA", order);

        // ⭐ Dùng launcher mới để chờ kết quả hủy đơn hàng
        orderDetailActivityResultLauncher.launch(intent);
    }


    @Override
    public void onReviewClicked(int orderId) {
        Intent intent = new Intent(this, ReviewActivity.class);
        intent.putExtra("orderID", orderId);
        reviewActivityResultLauncher.launch(intent);
    }

    @Override
    public void onRefundClicked(int orderId) {
        Toast.makeText(this, "Yêu cầu Hoàn/Đổi hàng đơn " + orderId, Toast.LENGTH_SHORT).show();
    }

    @Override
    public void onTrackClicked(int orderId) {
        Toast.makeText(this, "Theo dõi đơn hàng " + orderId, Toast.LENGTH_SHORT).show();
    }

    @Override
    public void onBuyAgainClicked(int orderId) {
        Fragment fragment = getSupportFragmentManager()
                .findFragmentByTag("f" + viewPagerOrders.getCurrentItem());

        if (fragment instanceof OrderFragment) {
            ((OrderFragment) fragment).executeBuyAgain(orderId);
        } else {
            Toast.makeText(this, "Lỗi: Không thể tìm thấy trang đơn hàng hiện tại.", Toast.LENGTH_LONG).show();
            Log.e("YourOrdersActivity", "Fragment for BuyAgain not found or wrong instance.");
        }
    }
}