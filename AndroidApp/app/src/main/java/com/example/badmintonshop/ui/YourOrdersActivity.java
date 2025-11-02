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
import com.example.badmintonshop.network.dto.OrderDto;
import com.google.android.material.tabs.TabLayout;
import com.google.android.material.tabs.TabLayoutMediator;
import com.google.android.material.appbar.MaterialToolbar;

public class YourOrdersActivity extends AppCompatActivity implements OrderAdapter.OrderAdapterListener {

    private ViewPager2 viewPagerOrders;
    private TabLayout tabLayoutOrders;
    private MaterialToolbar toolbar;

    private ActivityResultLauncher<Intent> reviewActivityResultLauncher;
    private ActivityResultLauncher<Intent> orderDetailActivityResultLauncher;

    private final String[] tabTitles = {"All orders", "Processing", "Shipped", "Delivered", "Cancelled", "Refunded"};

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_your_orders);

        toolbar = findViewById(R.id.toolbar);
        viewPagerOrders = findViewById(R.id.view_pager_orders);
        tabLayoutOrders = findViewById(R.id.tab_layout_orders);

        // Initialize Review Launcher
        reviewActivityResultLauncher = registerForActivityResult(
                new ActivityResultContracts.StartActivityForResult(),
                result -> {
                    if (result.getResultCode() == RESULT_OK) {
                        Toast.makeText(this, "Order reviewed, updating list...", Toast.LENGTH_SHORT).show();
                        refreshAllOrderFragments();
                    }
                }
        );

        // Initialize Order Detail Launcher
        orderDetailActivityResultLauncher = registerForActivityResult(
                new ActivityResultContracts.StartActivityForResult(),
                result -> {
                    if (result.getResultCode() == RESULT_OK) {
                        Toast.makeText(this, "Order status updated.", Toast.LENGTH_SHORT).show();
                        refreshAllOrderFragments();
                    }
                }
        );

        setSupportActionBar(toolbar);
        if (getSupportActionBar() != null) {
            getSupportActionBar().setTitle("Your Orders");
            getSupportActionBar().setDisplayHomeAsUpEnabled(true);
        }

        setupViewPagerAndTabs();

        // ⭐ XỬ LÝ DEEP LINK KHI ACTIVITY MỚI TẠO
        handleIntent(getIntent());
    }

    // ⭐ XỬ LÝ DEEP LINK KHI ACTIVITY ĐÃ ĐANG CHẠY
    @Override
    protected void onNewIntent(Intent intent) {
        super.onNewIntent(intent);
        setIntent(intent);
        Log.d("YourOrdersActivity", "onNewIntent called, handling deep link...");
        handleIntent(intent);
    }

    // ⭐ HÀM XỬ LÝ DEEP LINK TỪ VNPAY
    private void handleIntent(Intent intent) {
        String action = intent.getAction();
        Uri data = intent.getData();

        // Kiểm tra deep link "badmintonshop://yourorders?status=...&orderID=..."
        if (Intent.ACTION_VIEW.equals(action) && data != null && "yourorders".equals(data.getHost())) {
            String status = data.getQueryParameter("status");
            String orderId = data.getQueryParameter("orderID");

            Log.d("VNPAY_DEEPLINK", "Received status: " + status + ", OrderID: " + orderId);

            if (status != null && status.startsWith("success")) {
                Toast.makeText(this, "Thanh toán thành công! Đơn hàng #" + orderId + " đang xử lý.", Toast.LENGTH_LONG).show();
                viewPagerOrders.setCurrentItem(1, true); // Chuyển sang tab "Processing"
            } else if ("failed_cancelled".equals(status)) {
                Toast.makeText(this, "Thanh toán thất bại, đơn hàng #" + orderId + " đã bị hủy.", Toast.LENGTH_LONG).show();
                viewPagerOrders.setCurrentItem(4, true); // Chuyển sang tab "Cancelled"
            } else if ("hash_mismatch".equals(status)) {
                Toast.makeText(this, "Lỗi bảo mật giao dịch VNPay.", Toast.LENGTH_LONG).show();
            } else if ("error".equals(status)) {
                Toast.makeText(this, "Lỗi xử lý thanh toán.", Toast.LENGTH_LONG).show();
            }

            // ⭐ QUAN TRỌNG: RELOAD TẤT CẢ FRAGMENTS ĐỂ CẬP NHẬT TRẠNG THÁI MỚI
            refreshAllOrderFragments();
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

        viewPagerOrders.setCurrentItem(0, false);
    }

    // ⭐ LÀM MỚI TẤT CẢ FRAGMENTS
    private void refreshAllOrderFragments() {
        Log.d("YourOrdersActivity", "Forcing refresh on all active OrderFragments.");
        for (Fragment fragment : getSupportFragmentManager().getFragments()) {
            if (fragment instanceof OrderFragment && fragment.isAdded()) {
                ((OrderFragment) fragment).fetchOrders();
            }
        }
    }

    // --- IMPLEMENT OrderAdapter.OrderAdapterListener ---

    @Override
    public void onOrderClicked(OrderDto order) {
        Intent intent = new Intent(this, OrderDetailActivity.class);
        intent.putExtra("ORDER_DETAIL_DATA", order);
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
        Toast.makeText(this, "Request Return/Refund for order " + orderId, Toast.LENGTH_SHORT).show();
    }

    @Override
    public void onTrackClicked(int orderId) {
        Toast.makeText(this, "Track order " + orderId, Toast.LENGTH_SHORT).show();
    }

    @Override
    public void onBuyAgainClicked(int orderId) {
        Fragment fragment = getSupportFragmentManager()
                .findFragmentByTag("f" + viewPagerOrders.getCurrentItem());

        if (fragment instanceof OrderFragment) {
            ((OrderFragment) fragment).executeBuyAgain(orderId);
            Toast.makeText(this, "Adding items to your cart...", Toast.LENGTH_SHORT).show();
        } else {
            Toast.makeText(this, "Error: Could not process 'Buy Again'.", Toast.LENGTH_LONG).show();
        }
    }

    @Override
    public void onListRepayClicked(int orderId) {
        Log.d("YourOrdersActivity", "Handling onListRepayClicked for order ID: " + orderId);
        Fragment fragment = getSupportFragmentManager()
                .findFragmentByTag("f" + viewPagerOrders.getCurrentItem());

        if (fragment instanceof OrderFragment) {
            ((OrderFragment) fragment).initiateRepayment(orderId);
        } else {
            Toast.makeText(this, "Error processing payment.", Toast.LENGTH_SHORT).show();
        }
    }
}