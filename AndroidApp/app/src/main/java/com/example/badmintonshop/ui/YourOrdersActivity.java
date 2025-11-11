// File: app/src/main/java/com/example/badmintonshop/ui/YourOrdersActivity.java
// NỘI DUNG ĐÃ SỬA

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
    private ActivityResultLauncher<Intent> refundActivityResultLauncher;
    // ⭐ MỚI: Thêm launcher cho màn hình Tracking
    private ActivityResultLauncher<Intent> trackingActivityResultLauncher;


    private final String[] tabTitles = {
            "All orders",
            "Processing",
            "Shipped",
            "Delivered",
            "Cancelled",
            "Refund Requested",
            "Refunded"
    };
    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_your_orders);

        toolbar = findViewById(R.id.toolbar);
        viewPagerOrders = findViewById(R.id.view_pager_orders);
        tabLayoutOrders = findViewById(R.id.tab_layout_orders);

        // Launcher cho Đánh giá
        reviewActivityResultLauncher = registerForActivityResult(
                new ActivityResultContracts.StartActivityForResult(),
                result -> {
                    if (result.getResultCode() == RESULT_OK) {
                        Toast.makeText(this, "Đã đánh giá, đang cập nhật...", Toast.LENGTH_SHORT).show();
                        refreshAllOrderFragments();
                    }
                }
        );

        // Launcher cho Chi tiết đơn hàng (OrderDetail)
        orderDetailActivityResultLauncher = registerForActivityResult(
                new ActivityResultContracts.StartActivityForResult(),
                result -> {
                    if (result.getResultCode() == RESULT_OK) {
                        Toast.makeText(this, "Trạng thái đơn hàng đã cập nhật.", Toast.LENGTH_SHORT).show();
                        refreshAllOrderFragments();
                    }
                }
        );

        // ⭐ MỚI: Launcher cho Theo dõi đơn hàng (OrderTracking)
        trackingActivityResultLauncher = registerForActivityResult(
                new ActivityResultContracts.StartActivityForResult(),
                result -> {
                    // Dùng chung logic refresh giống như OrderDetail
                    if (result.getResultCode() == RESULT_OK) {
                        Toast.makeText(this, "Trạng thái đơn hàng đã cập nhật.", Toast.LENGTH_SHORT).show();
                        refreshAllOrderFragments();
                    }
                }
        );


        // Launcher cho Trả hàng
        refundActivityResultLauncher = registerForActivityResult(
                new ActivityResultContracts.StartActivityForResult(),
                result -> {
                    if (result.getResultCode() == RESULT_OK) {
                        Toast.makeText(this, "Đã gửi yêu cầu. Đang cập nhật...", Toast.LENGTH_SHORT).show();
                        refreshAllOrderFragments();
                        viewPagerOrders.setCurrentItem(5, true); // Chuyển tab "Refund Requested"
                    }
                }
        );

        setSupportActionBar(toolbar);
        if (getSupportActionBar() != null) {
            getSupportActionBar().setTitle("Your Orders");
            getSupportActionBar().setDisplayHomeAsUpEnabled(true);
        }

        setupViewPagerAndTabs();

        handleIntent(getIntent());
    }

    @Override
    protected void onNewIntent(Intent intent) {
        super.onNewIntent(intent);
        setIntent(intent);
        Log.d("YourOrdersActivity", "onNewIntent called, handling deep link...");
        handleIntent(intent);
    }

    private void handleIntent(Intent intent) {
        String action = intent.getAction();
        Uri data = intent.getData();

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

    private void refreshAllOrderFragments() {
        Log.d("YourOrdersActivity", "Forcing refresh on all active OrderFragments.");
        for (Fragment fragment : getSupportFragmentManager().getFragments()) {
            if (fragment instanceof OrderFragment && fragment.isAdded()) {
                ((OrderFragment) fragment).fetchOrders();
            }
        }
    }

    // --- IMPLEMENT OrderAdapter.OrderAdapterListener ---

    /**
     * Click vào CẢ ITEM -> Mở OrderDetailActivity (Chi tiết đơn hàng)
     */
    @Override
    public void onOrderClicked(OrderDto order) {
        // Mở OrderDetailActivity (như code gốc của bạn)
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
        Intent intent = new Intent(this, RequestRefundActivity.class);
        intent.putExtra("ORDER_ID", orderId);
        intent.putExtra("CUSTOMER_ID", getCurrentCustomerId());
        refundActivityResultLauncher.launch(intent);
    }

    /**
     * Click vào NÚT "TRACK" -> Mở OrderTrackingActivity (Theo dõi)
     */
    @Override
    public void onTrackClicked(int orderId) {
        // ⭐ SỬA LỖI: Mở OrderTrackingActivity
        Intent intent = new Intent(this, OrderTrackingActivity.class);
        intent.putExtra("ORDER_ID", orderId);
        // Dùng launcher mới (hoặc launcher cũ cũng được)
        trackingActivityResultLauncher.launch(intent);
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