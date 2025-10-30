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
// Loại bỏ import không cần thiết
// import com.example.badmintonshop.network.ApiClient;
// import com.example.badmintonshop.network.ApiService;
import com.example.badmintonshop.network.dto.OrderDto;
import com.google.android.material.tabs.TabLayout;
import com.google.android.material.tabs.TabLayoutMediator;
import com.google.android.material.appbar.MaterialToolbar;

public class YourOrdersActivity extends AppCompatActivity implements OrderAdapter.OrderAdapterListener {

    private ViewPager2 viewPagerOrders;
    private TabLayout tabLayoutOrders;
    private MaterialToolbar toolbar;
    // private ApiService apiService; // Not strictly needed here unless used directly

    // Launcher for Reviews
    private ActivityResultLauncher<Intent> reviewActivityResultLauncher;

    // Launcher for Order Detail (handles Cancel result)
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
                        refreshAllOrderFragments(); // ⭐ SỬ DỤNG HÀM LÀM MỚI TẤT CẢ
                    }
                }
        );

        // Initialize Order Detail Launcher (for Cancel)
        orderDetailActivityResultLauncher = registerForActivityResult(
                new ActivityResultContracts.StartActivityForResult(),
                result -> {
                    if (result.getResultCode() == RESULT_OK) {
                        Toast.makeText(this, "Order status updated.", Toast.LENGTH_SHORT).show();
                        // Nếu có thay đổi trạng thái từ OrderDetailActivity
                        refreshAllOrderFragments(); // ⭐ SỬ DỤNG HÀM LÀM MỚI TẤT CẢ
                    }
                }
        );

        setSupportActionBar(toolbar);
        if (getSupportActionBar() != null) {
            getSupportActionBar().setTitle("Your Orders");
            getSupportActionBar().setDisplayHomeAsUpEnabled(true);
        }

        setupViewPagerAndTabs();

        // Handle deep link from VNPay callback if Activity is newly created
        handleIntent(getIntent());
    }

    // Handle deep link if Activity is already running
    @Override
    protected void onNewIntent(Intent intent) {
        super.onNewIntent(intent);
        setIntent(intent); // Update the intent this Activity is holding
        handleIntent(intent);
    }

    // Process intent for VNPay results
    private void handleIntent(Intent intent) {
        String action = intent.getAction();
        Uri data = intent.getData();

        // Check if it's a deep link from your app's scheme
        if (Intent.ACTION_VIEW.equals(action) && data != null && "yourorders".equals(data.getHost())) {
            String status = data.getQueryParameter("status");
            String orderId = data.getQueryParameter("orderID"); // Make sure parameter name matches PHP

            Log.d("VNPAY_DEEPLINK", "Received status: " + status + ", OrderID: " + orderId);

            if (status != null && status.startsWith("success")) { // Bao gồm cả "success" và "success_..."
                Toast.makeText(this, "Payment successful! Order #" + orderId + " is processing.", Toast.LENGTH_LONG).show();
                viewPagerOrders.setCurrentItem(1, true); // Go to "Processing" tab
            } else if ("failed".equals(status) || "failed_cancelled".equals(status)) {
                Toast.makeText(this, "Payment failed for order #" + orderId + " and it was cancelled.", Toast.LENGTH_LONG).show();
                viewPagerOrders.setCurrentItem(4, true); // Go to "Cancelled" tab
            } else if ("security_error".equals(status)) {
                Toast.makeText(this, "VNPay transaction security error.", Toast.LENGTH_LONG).show();
            }
            // Gọi làm mới TẤT CẢ Fragments để đảm bảo trạng thái mới được lấy từ API
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

    // Helper to get Customer ID
    public int getCurrentCustomerId() {
        SharedPreferences sp = getSharedPreferences("auth", MODE_PRIVATE);
        return sp.getInt("customerID", -1);
    }

    // Setup ViewPager and Tabs
    private void setupViewPagerAndTabs() {
        // Use the correct PagerAdapter name
        OrderPagerAdapter adapter = new OrderPagerAdapter(this, tabTitles);
        viewPagerOrders.setAdapter(adapter);

        new TabLayoutMediator(tabLayoutOrders, viewPagerOrders, (tab, position) -> {
            tab.setText(tabTitles[position]);
        }).attach();

        // Optionally set default tab (e.g., "Processing")
        viewPagerOrders.setCurrentItem(0, false); // Start at "All orders" initially
    }

    // ⭐ THAY THẾ HÀM CŨ: Làm mới dữ liệu trong TẤT CẢ các Fragment đang hoạt động
    private void refreshAllOrderFragments() {
        Log.d("YourOrdersActivity", "Forcing refresh on all active OrderFragments.");
        // Duyệt qua tất cả các Fragment đang hoạt động
        for (Fragment fragment : getSupportFragmentManager().getFragments()) {
            if (fragment instanceof OrderFragment) {
                // Kiểm tra xem Fragment đã được gắn vào Activity và có thể tương tác
                if (fragment.isAdded()) {
                    ((OrderFragment) fragment).fetchOrders();
                }
            }
        }
    }


    // -----------------------------------------------------------------
    // IMPLEMENTATION of OrderAdapter.OrderAdapterListener
    // -----------------------------------------------------------------

    // Handle clicks on the entire order item
    @Override
    public void onOrderClicked(OrderDto order) {
        Intent intent = new Intent(this, OrderDetailActivity.class);
        intent.putExtra("ORDER_DETAIL_DATA", order); // Pass the whole OrderDto
        // Use the launcher to wait for a potential RESULT_OK from cancelling the order
        orderDetailActivityResultLauncher.launch(intent);
    }

    // Handle clicks on the "Leave a review" button
    @Override
    public void onReviewClicked(int orderId) {
        Intent intent = new Intent(this, ReviewActivity.class);
        intent.putExtra("orderID", orderId);
        // Use launcher to wait for RESULT_OK after review submission
        reviewActivityResultLauncher.launch(intent);
    }

    // Handle clicks on the "Return/Refund" button (placeholder)
    @Override
    public void onRefundClicked(int orderId) {
        Toast.makeText(this, "Request Return/Refund for order " + orderId, Toast.LENGTH_SHORT).show();
        // Implement refund logic/activity start here
    }

    // Handle clicks on the "Track" button (placeholder)
    @Override
    public void onTrackClicked(int orderId) {
        Toast.makeText(this, "Track order " + orderId, Toast.LENGTH_SHORT).show();
        // Implement tracking logic/activity start here
    }

    // Handle clicks on the "Buy Again" button
    @Override
    public void onBuyAgainClicked(int orderId) {
        Fragment fragment = getSupportFragmentManager()
                .findFragmentByTag("f" + viewPagerOrders.getCurrentItem());

        if (fragment instanceof OrderFragment) {
            ((OrderFragment) fragment).executeBuyAgain(orderId);
            // Show confirmation Toast in the Activity after triggering the fragment action
            Toast.makeText(this, "Adding items to your cart...", Toast.LENGTH_SHORT).show();
        } else {
            Toast.makeText(this, "Error: Could not process 'Buy Again'.", Toast.LENGTH_LONG).show();
            Log.e("YourOrdersActivity", "Fragment for BuyAgain not found or wrong instance.");
        }
    }

    // ⭐ THÊM: Implement phương thức mới for the list repay button
    @Override
    public void onListRepayClicked(int orderId) {
        Log.d("YourOrdersActivity", "Handling onListRepayClicked for order ID: " + orderId);
        // Find the current fragment
        Fragment fragment = getSupportFragmentManager()
                .findFragmentByTag("f" + viewPagerOrders.getCurrentItem());

        if (fragment instanceof OrderFragment) {
            // Call the fragment's method to handle repayment
            ((OrderFragment) fragment).initiateRepayment(orderId);
        } else {
            Toast.makeText(this, "Error processing payment.", Toast.LENGTH_SHORT).show();
            Log.e("YourOrdersActivity", "Current fragment is not OrderFragment, cannot handle repay click.");
        }
    }

    // ⭐ XÓA HÀM refreshCurrentOrderFragment CŨ (đã được thay thế bằng refreshAllOrderFragments)
}