package com.example.badmintonshop.ui;

import android.content.Intent;
import android.content.SharedPreferences;
import android.os.Bundle;
import android.widget.TextView;
import android.widget.Toast;

import androidx.appcompat.app.AlertDialog;
import androidx.appcompat.app.AppCompatActivity;
import androidx.recyclerview.widget.RecyclerView;
import androidx.recyclerview.widget.StaggeredGridLayoutManager;
import androidx.viewpager2.widget.ViewPager2;

import com.example.badmintonshop.R;
import com.example.badmintonshop.adapter.BannerAdapter;
import com.example.badmintonshop.adapter.ProductAdapter;
import com.example.badmintonshop.network.ApiClient;
import com.example.badmintonshop.network.ApiService;
import com.example.badmintonshop.network.dto.ProductDto;
import com.example.badmintonshop.network.dto.ProductListResponse;
import com.example.badmintonshop.network.dto.SliderDto;
import com.google.android.material.bottomnavigation.BottomNavigationView;
import com.google.android.material.tabs.TabLayout;

import java.util.List;
import java.util.Timer;
import java.util.TimerTask;

import retrofit2.Call;
import retrofit2.Callback;
import retrofit2.Response;

public class HomeActivity extends AppCompatActivity {

    private static final String BASE_URL = "http://10.0.2.2/api/BadmintonShop/";
    private ApiService api;

    private RecyclerView recyclerMainGrid;
    private TabLayout tabLayout;
    private ViewPager2 bannerSlider;
    private TextView tvSearchBar;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_home);

        // Khởi tạo ApiService
        api = ApiClient.get(BASE_URL).create(ApiService.class);

        // Ánh xạ view
        recyclerMainGrid = findViewById(R.id.recyclerMainGrid);
        tabLayout = findViewById(R.id.tabLayout);
        bannerSlider = findViewById(R.id.bannerSlider);
        tvSearchBar = findViewById(R.id.tvSearchBar);
        BottomNavigationView bottomNav = findViewById(R.id.bottomNav);
        bottomNav.setSelectedItemId(R.id.nav_home);

        recyclerMainGrid.setLayoutManager(
                new StaggeredGridLayoutManager(2, StaggeredGridLayoutManager.VERTICAL)
        );

        setupTabs();
        loadProducts();
        loadBanners();

        // Khi bấm vào ô search
        tvSearchBar.setOnClickListener(v -> {
            startActivity(new Intent(HomeActivity.this, SearchActivity.class));
        });

        // Xử lý bottom navigation
        bottomNav.setOnItemSelectedListener(item -> {
            int id = item.getItemId();
            if (id == R.id.nav_home) {
                return true;
            } else if (id == R.id.nav_categories) {
                startActivity(new Intent(this, CategoryActivity.class));
                return true;
            } else if (id == R.id.nav_you) {
                showLogoutConfirmDialog();
                return true;
            } else if (id == R.id.nav_cart) {
                Toast.makeText(this, "Cart clicked", Toast.LENGTH_SHORT).show();
                return true;
            }
            return false;
        });
    }

    // 🏷️ Danh mục hiển thị ở tab
    private void setupTabs() {
        tabLayout.addTab(tabLayout.newTab().setText("All"));
        tabLayout.addTab(tabLayout.newTab().setText("Vợt cầu lông"));
        tabLayout.addTab(tabLayout.newTab().setText("Giày cầu lông"));
        tabLayout.addTab(tabLayout.newTab().setText("Quần áo cầu lông"));
        tabLayout.addTab(tabLayout.newTab().setText("Phụ kiện"));

        tabLayout.addOnTabSelectedListener(new TabLayout.OnTabSelectedListener() {
            @Override
            public void onTabSelected(TabLayout.Tab tab) {
                String category = tab.getText().toString();
                if (category.equalsIgnoreCase("All")) {
                    loadProducts();
                } else {
                    loadProductsByCategory(category);
                }
            }

            @Override public void onTabUnselected(TabLayout.Tab tab) {}
            @Override public void onTabReselected(TabLayout.Tab tab) {}
        });
    }

    // 📦 Tải toàn bộ sản phẩm
    private void loadProducts() {
        api.getProducts(1, 40).enqueue(new Callback<ProductListResponse>() {
            @Override
            public void onResponse(Call<ProductListResponse> call, Response<ProductListResponse> response) {
                if (!response.isSuccessful() || response.body() == null) {
                    Toast.makeText(HomeActivity.this, "Không tải được sản phẩm!", Toast.LENGTH_SHORT).show();
                    return;
                }

                List<ProductDto> list = response.body().getItems();
                if (list == null || list.isEmpty()) {
                    Toast.makeText(HomeActivity.this, "Chưa có sản phẩm nào!", Toast.LENGTH_SHORT).show();
                    return;
                }

                recyclerMainGrid.setAdapter(new ProductAdapter(HomeActivity.this, list));
            }

            @Override
            public void onFailure(Call<ProductListResponse> call, Throwable t) {
                Toast.makeText(HomeActivity.this, "Lỗi kết nối: " + t.getMessage(), Toast.LENGTH_LONG).show();
            }
        });
    }

    // 🏷️ Tải sản phẩm theo danh mục
    private void loadProductsByCategory(String category) {
        api.getProductsByCategory(category).enqueue(new Callback<ProductListResponse>() {
            @Override
            public void onResponse(Call<ProductListResponse> call, Response<ProductListResponse> response) {
                if (!response.isSuccessful() || response.body() == null) {
                    Toast.makeText(HomeActivity.this, "Không tải được sản phẩm!", Toast.LENGTH_SHORT).show();
                    return;
                }

                List<ProductDto> list = response.body().getItems();
                if (list == null || list.isEmpty()) {
                    Toast.makeText(HomeActivity.this, "Không có sản phẩm trong danh mục này", Toast.LENGTH_SHORT).show();
                    recyclerMainGrid.setAdapter(null);
                    return;
                }

                recyclerMainGrid.setAdapter(new ProductAdapter(HomeActivity.this, list));
            }

            @Override
            public void onFailure(Call<ProductListResponse> call, Throwable t) {
                Toast.makeText(HomeActivity.this, "Lỗi kết nối: " + t.getMessage(), Toast.LENGTH_SHORT).show();
            }
        });
    }

    // 🖼️ Tải banner quảng cáo
    private void loadBanners() {
        api.getSliders().enqueue(new Callback<List<SliderDto>>() {
            @Override
            public void onResponse(Call<List<SliderDto>> call, Response<List<SliderDto>> response) {
                if (response.isSuccessful() && response.body() != null) {
                    List<SliderDto> banners = response.body();
                    BannerAdapter adapter = new BannerAdapter(HomeActivity.this, banners);
                    bannerSlider.setAdapter(adapter);

                    new Timer().scheduleAtFixedRate(new TimerTask() {
                        @Override
                        public void run() {
                            runOnUiThread(() -> {
                                int currentItem = bannerSlider.getCurrentItem();
                                if (currentItem + 1 < banners.size()) {
                                    bannerSlider.setCurrentItem(currentItem + 1);
                                } else {
                                    bannerSlider.setCurrentItem(0);
                                }
                            });
                        }
                    }, 4000, 4000);
                }
            }

            @Override
            public void onFailure(Call<List<SliderDto>> call, Throwable t) {}
        });
    }

    // 🔒 Đăng xuất
    private void showLogoutConfirmDialog() {
        new AlertDialog.Builder(this)
                .setTitle("Xác nhận đăng xuất")
                .setMessage("Bạn có chắc chắn muốn đăng xuất không?")
                .setPositiveButton("Đăng xuất", (dialog, which) -> logout())
                .setNegativeButton("Hủy", null)
                .show();
    }

    private void logout() {
        SharedPreferences sp = getSharedPreferences("auth", MODE_PRIVATE);
        sp.edit().clear().apply();
        Toast.makeText(this, "Đã đăng xuất", Toast.LENGTH_SHORT).show();
        Intent i = new Intent(this, LoginActivity.class);
        i.setFlags(Intent.FLAG_ACTIVITY_NEW_TASK | Intent.FLAG_ACTIVITY_CLEAR_TASK);
        startActivity(i);
        finish();
    }
}
