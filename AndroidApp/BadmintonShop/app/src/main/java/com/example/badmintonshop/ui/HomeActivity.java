package com.example.badmintonshop.ui;

import android.content.Intent;
import android.content.SharedPreferences;
import android.os.Bundle;
import android.widget.Toast;

import androidx.appcompat.app.AlertDialog;
import androidx.appcompat.app.AppCompatActivity;
import androidx.recyclerview.widget.LinearLayoutManager;
import androidx.recyclerview.widget.RecyclerView;

import com.example.badmintonshop.R;
import com.example.badmintonshop.adapter.ProductAdapter;
import com.example.badmintonshop.network.ApiClient;
import com.example.badmintonshop.network.ApiService;
import com.example.badmintonshop.network.dto.ProductDto;
import com.example.badmintonshop.network.dto.ProductListResponse;
import com.google.android.material.bottomnavigation.BottomNavigationView;
import androidx.viewpager2.widget.ViewPager2;
import retrofit2.Call;
import retrofit2.Callback;
import retrofit2.Response;
import com.example.badmintonshop.adapter.BannerAdapter;
import com.example.badmintonshop.network.dto.SliderDto;
import java.util.Timer;
import java.util.TimerTask;

import java.util.List;

import retrofit2.Call;
import retrofit2.Callback;
import retrofit2.Response;

public class HomeActivity extends AppCompatActivity {

    private static final String BASE_URL = "http://10.0.2.2/api/BadmintonShop/";
    private ApiService api;

    private RecyclerView rvComingSoon, rvBestSelling, rvNewArrivals, rvFeatured;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_home);


        api = ApiClient.get(BASE_URL).create(ApiService.class);

        // Ánh xạ
        rvComingSoon = findViewById(R.id.recyclerComingSoon);
        rvBestSelling = findViewById(R.id.recyclerBestSelling);
        rvNewArrivals = findViewById(R.id.recyclerNewArrivals);
        rvFeatured = findViewById(R.id.recyclerFeatured);

        // Layout manager ngang
        rvComingSoon.setLayoutManager(new LinearLayoutManager(this, LinearLayoutManager.HORIZONTAL, false));
        rvBestSelling.setLayoutManager(new LinearLayoutManager(this, LinearLayoutManager.HORIZONTAL, false));
        rvNewArrivals.setLayoutManager(new LinearLayoutManager(this, LinearLayoutManager.HORIZONTAL, false));
        rvFeatured.setLayoutManager(new LinearLayoutManager(this, LinearLayoutManager.HORIZONTAL, false));

        // Gọi API để lấy danh sách sản phẩm
        loadProducts();
        loadBanners();
        // 🧭 Bắt sự kiện Bottom Navigation
        BottomNavigationView bottomNav = findViewById(R.id.bottomNav);
        bottomNav.setOnItemSelectedListener(item -> {
            int id = item.getItemId();

            if (id == R.id.action_logout) {
                showLogoutConfirmDialog();
                return true;
            } else if (id == R.id.nav_cart) {
                Toast.makeText(this, "Giỏ hàng đang phát triển", Toast.LENGTH_SHORT).show();
                return true;
            } else if (id == R.id.nav_profile) {
                Toast.makeText(this, "Trang tài khoản sắp ra mắt!", Toast.LENGTH_SHORT).show();
                return true;
            }
            return false;
        });
    }



    private void loadProducts() {
        api.getProducts(1, 20).enqueue(new Callback<ProductListResponse>() {
            @Override
            public void onResponse(Call<ProductListResponse> call, Response<ProductListResponse> response) {
                if (!response.isSuccessful() || response.body() == null) {
                    Toast.makeText(HomeActivity.this, "Không tải được dữ liệu!", Toast.LENGTH_SHORT).show();
                    return;
                }

                List<ProductDto> list = response.body().getItems();
                if (list == null || list.isEmpty()) {
                    Toast.makeText(HomeActivity.this, "Chưa có sản phẩm nào!", Toast.LENGTH_SHORT).show();
                    return;
                }

                ProductAdapter adapter = new ProductAdapter(HomeActivity.this, list);
                rvComingSoon.setAdapter(adapter);
                rvBestSelling.setAdapter(adapter);
                rvNewArrivals.setAdapter(adapter);
                rvFeatured.setAdapter(adapter);
            }

            @Override
            public void onFailure(Call<ProductListResponse> call, Throwable t) {
                Toast.makeText(HomeActivity.this, "Lỗi kết nối: " + t.getMessage(), Toast.LENGTH_LONG).show();
            }
        });
    }
    private void loadBanners() {
        ApiService api = ApiClient.get("http://10.0.2.2/api/BadmintonShop/").create(ApiService.class);
        ViewPager2 bannerSlider = findViewById(R.id.bannerSlider);

        api.getSliders().enqueue(new Callback<List<SliderDto>>() {
            @Override
            public void onResponse(Call<List<SliderDto>> call, Response<List<SliderDto>> response) {
                if (!response.isSuccessful() || response.body() == null) {
                    Toast.makeText(HomeActivity.this, "Không tải được banner", Toast.LENGTH_SHORT).show();
                    return;
                }

                List<SliderDto> banners = response.body();
                BannerAdapter adapter = new BannerAdapter(HomeActivity.this, banners);
                bannerSlider.setAdapter(adapter);

                // Tự động chuyển slide mỗi 4 giây
                new Timer().scheduleAtFixedRate(new TimerTask() {
                    int current = 0;
                    @Override
                    public void run() {
                        runOnUiThread(() -> {
                            if (current >= banners.size()) current = 0;
                            bannerSlider.setCurrentItem(current++, true);
                        });
                    }
                }, 4000, 4000);
            }

            @Override
            public void onFailure(Call<List<SliderDto>> call, Throwable t) {
                Toast.makeText(HomeActivity.this, "Lỗi kết nối API", Toast.LENGTH_SHORT).show();
            }
        });
    }
    private void showLogoutConfirmDialog() {
        new AlertDialog.Builder(this)
                .setTitle("Xác nhận đăng xuất")
                .setMessage("Bạn có chắc chắn muốn đăng xuất không?")
                .setPositiveButton("Đăng xuất", (dialog, which) -> logout())
                .setNegativeButton("Hủy", (dialog, which) -> dialog.dismiss())
                .setCancelable(true)
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
