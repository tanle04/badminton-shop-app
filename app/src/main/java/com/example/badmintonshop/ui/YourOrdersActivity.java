package com.example.badmintonshop.ui;

import android.content.Intent;
import android.content.SharedPreferences;
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
import com.google.android.material.tabs.TabLayout;
import com.google.android.material.tabs.TabLayoutMediator;
import com.google.android.material.appbar.MaterialToolbar;

// ⭐ Không cần import các thư viện Retrofit/API trong Activity này nữa (chúng đã chuyển sang Fragment)

public class YourOrdersActivity extends AppCompatActivity implements OrderAdapter.OrderAdapterListener {

    private ViewPager2 viewPagerOrders;
    private TabLayout tabLayoutOrders;
    private MaterialToolbar toolbar;
    private ApiService apiService; // Giữ nguyên khai báo, nhưng không dùng để gọi API AddToCart

    private ActivityResultLauncher<Intent> reviewActivityResultLauncher;

    private final String[] tabTitles = {"All orders", "Processing", "Shipped", "Delivered", "Cancelled", "Refunded"};

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_your_orders);

        apiService = ApiClient.getApiService();

        toolbar = findViewById(R.id.toolbar);
        viewPagerOrders = findViewById(R.id.view_pager_orders);
        tabLayoutOrders = findViewById(R.id.tab_layout_orders);

        // Khởi tạo Activity Result Launcher
        reviewActivityResultLauncher = registerForActivityResult(
                new ActivityResultContracts.StartActivityForResult(),
                result -> {
                    if (result.getResultCode() == RESULT_OK) {
                        Toast.makeText(this, "Đơn hàng đã được đánh giá, đang cập nhật...", Toast.LENGTH_SHORT).show();
                        refreshCurrentOrderFragment();
                    }
                }
        );


        setSupportActionBar(toolbar);
        if (getSupportActionBar() != null) {
            getSupportActionBar().setTitle("Đơn hàng của bạn");
            getSupportActionBar().setDisplayHomeAsUpEnabled(true);
        }

        setupViewPagerAndTabs();
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

    // HÀM REFRESH DỮ LIỆU TRONG FRAGMENT HIỆN TẠI (Đã sửa lỗi quyền truy cập trong OrderFragment)
    private void refreshCurrentOrderFragment() {
        // Cần tìm Fragment bằng tag được tạo bởi ViewPager
        Fragment fragment = getSupportFragmentManager()
                .findFragmentByTag("f" + viewPagerOrders.getCurrentItem());

        if (fragment instanceof OrderFragment) {
            // Gọi hàm public fetchOrders() trên Fragment
            ((OrderFragment) fragment).fetchOrders();
        }
    }


    // -----------------------------------------------------------------
    // ⭐ TRIỂN KHAI CÁC PHƯƠNG THỨC CỦA OrderAdapter.OrderAdapterListener
    // -----------------------------------------------------------------

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

    // ⭐ ĐÃ SỬA LỖI: Triển khai đúng chữ ký onBuyAgainClicked(int orderId)
    // Logic gọi API AddToCart đã được chuyển sang OrderFragment.executeBuyAgain()
    @Override
    public void onBuyAgainClicked(int orderId) {
        // 1. Tìm Fragment đang hoạt động
        Fragment fragment = getSupportFragmentManager()
                .findFragmentByTag("f" + viewPagerOrders.getCurrentItem());

        if (fragment instanceof OrderFragment) {
            // 2. Fragment sẽ tìm OrderDto trong danh sách của nó và lặp gọi API AddToCart
            ((OrderFragment) fragment).executeBuyAgain(orderId);
        } else {
            Toast.makeText(this, "Lỗi: Không thể tìm thấy trang đơn hàng hiện tại.", Toast.LENGTH_LONG).show();
            Log.e("YourOrdersActivity", "Fragment for BuyAgain not found or wrong instance.");
        }
    }
}