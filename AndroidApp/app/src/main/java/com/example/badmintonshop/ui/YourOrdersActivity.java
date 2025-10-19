package com.example.badmintonshop.ui;

import android.content.Intent;
import android.content.SharedPreferences;
import android.os.Bundle;
import android.view.MenuItem;
import android.widget.Toast;

import androidx.annotation.NonNull;
import androidx.appcompat.app.AppCompatActivity;
import androidx.viewpager2.widget.ViewPager2;

import com.example.badmintonshop.R;
import com.example.badmintonshop.adapter.OrderPagerAdapter;
import com.google.android.material.tabs.TabLayout;
import com.google.android.material.tabs.TabLayoutMediator;
import com.google.android.material.appbar.MaterialToolbar;

public class YourOrdersActivity extends AppCompatActivity {

    private ViewPager2 viewPagerOrders;
    private TabLayout tabLayoutOrders;
    private MaterialToolbar toolbar;

    private final String[] tabTitles = {"All orders", "Processing", "Shipped", "Delivered", "Cancelled", "Refunded"};

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_your_orders);

        toolbar = findViewById(R.id.toolbar);
        viewPagerOrders = findViewById(R.id.view_pager_orders);
        tabLayoutOrders = findViewById(R.id.tab_layout_orders);

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

    // ⭐ PHƯƠNG THỨC TRUY CẬP CUSTOMER ID (PUBLIC)
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

        // Load tab Processing/Shipped (Index 1 or 2) on startup for better UX
        viewPagerOrders.setCurrentItem(1, false);
    }
}