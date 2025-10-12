package com.example.badmintonshop.ui;

import android.content.Intent;
import android.content.SharedPreferences;
import android.os.Bundle;
import android.widget.TextView;
import android.widget.Toast;

import androidx.appcompat.app.AlertDialog;
import androidx.appcompat.app.AppCompatActivity;
import androidx.recyclerview.widget.GridLayoutManager;
import androidx.recyclerview.widget.LinearLayoutManager;
import androidx.recyclerview.widget.RecyclerView;

import com.example.badmintonshop.R;
import com.example.badmintonshop.adapter.CategoryListAdapter;
import com.example.badmintonshop.adapter.ProductAdapter;
import com.example.badmintonshop.network.ApiClient;
import com.example.badmintonshop.network.ApiService;
import com.example.badmintonshop.network.dto.CategoryDto;
import com.example.badmintonshop.network.dto.CategoryListResponse;
import com.example.badmintonshop.network.dto.ProductDto;
import com.example.badmintonshop.network.dto.ProductListResponse;
import com.google.android.material.bottomnavigation.BottomNavigationView;

import java.util.ArrayList;
import java.util.List;

import retrofit2.Call;
import retrofit2.Callback;
import retrofit2.Response;


public class CategoryActivity extends AppCompatActivity {

    private RecyclerView recyclerCategoryList, recyclerProductGrid;
    private CategoryListAdapter categoryAdapter;
    private ProductAdapter productAdapter;
    private ApiService api;
    TextView tvSearchBarCategory;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_category);

        recyclerCategoryList = findViewById(R.id.recyclerCategoryList);
        recyclerProductGrid = findViewById(R.id.recyclerProductGrid);
        BottomNavigationView bottomNav = findViewById(R.id.bottomNav);

        // ✅ Khởi tạo API
        api = ApiClient.get("http://10.0.2.2/api/BadmintonShop/").create(ApiService.class);

        setupProductGrid();
        loadCategories(); // tải danh mục thật từ database
        tvSearchBarCategory= findViewById(R.id.tvSearchBarCategory);

        tvSearchBarCategory.setOnClickListener(v -> {
            startActivity(new Intent(CategoryActivity.this, SearchActivity.class));
        });
        // Xử lý bottom navigation
        bottomNav.setOnItemSelectedListener(item -> {
            int id = item.getItemId();
            if (id == R.id.nav_home) {
                startActivity(new Intent(this, HomeActivity.class));
                return true;
            } else if (id == R.id.nav_categories) {

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

    // =============================
    // 1️⃣ TẢI DANH MỤC
    // =============================
    private void loadCategories() {
        recyclerCategoryList.setLayoutManager(new LinearLayoutManager(this));

        api.getCategories().enqueue(new Callback<CategoryListResponse>() {
            @Override
            public void onResponse(Call<CategoryListResponse> call, Response<CategoryListResponse> response) {
                if (!response.isSuccessful() || response.body() == null) {
                    Toast.makeText(CategoryActivity.this, "Không tải được danh mục!", Toast.LENGTH_SHORT).show();
                    return;
                }

                List<CategoryDto> categories = response.body().getItems();
                if (categories == null || categories.isEmpty()) {
                    Toast.makeText(CategoryActivity.this, "Danh mục trống!", Toast.LENGTH_SHORT).show();
                    return;
                }

                // ✅ Thêm mục "Featured" ở đầu danh sách
                List<String> categoryNames = new ArrayList<>();
                categoryNames.add("Featured");
                for (CategoryDto c : categories) {
                    categoryNames.add(c.getCategoryName());
                }

                categoryAdapter = new CategoryListAdapter(categoryNames, categoryName -> {
                    loadProductsForCategory(categoryName);
                });

                recyclerCategoryList.setAdapter(categoryAdapter);

                // ✅ Tải sản phẩm cho danh mục đầu tiên
                loadProductsForCategory(categoryNames.get(0));
            }

            @Override
            public void onFailure(Call<CategoryListResponse> call, Throwable t) {
                Toast.makeText(CategoryActivity.this, "Lỗi kết nối: " + t.getMessage(), Toast.LENGTH_SHORT).show();
            }
        });
    }

    // =============================
    // 2️⃣ CẤU HÌNH LƯỚI SẢN PHẨM
    // =============================
    private void setupProductGrid() {
        GridLayoutManager grid = new GridLayoutManager(this, 2);
        recyclerProductGrid.setLayoutManager(grid);
        recyclerProductGrid.setPadding(8, 8, 8, 8);
        recyclerProductGrid.setClipToPadding(false);
    }

    // =============================
    // 3️⃣ LOAD SẢN PHẨM THEO DANH MỤC
    // =============================
    private void loadProductsForCategory(String categoryName) {
        if (categoryName.equalsIgnoreCase("Featured")) {
            // 🟢 Nếu chọn Featured → tải tất cả
            api.getProducts(1, 20).enqueue(new Callback<ProductListResponse>() {
                @Override
                public void onResponse(Call<ProductListResponse> call, Response<ProductListResponse> response) {
                    if (response.isSuccessful() && response.body() != null) {
                        List<ProductDto> products = response.body().getItems();
                        productAdapter = new ProductAdapter(CategoryActivity.this, products);
                        recyclerProductGrid.setAdapter(productAdapter);
                    } else {
                        Toast.makeText(CategoryActivity.this, "Không tải được sản phẩm!", Toast.LENGTH_SHORT).show();
                    }
                }

                @Override
                public void onFailure(Call<ProductListResponse> call, Throwable t) {
                    Toast.makeText(CategoryActivity.this, "Lỗi: " + t.getMessage(), Toast.LENGTH_SHORT).show();
                }
            });
            return;
        }

        // 🔵 Nếu là danh mục khác → gọi filter API
        api.getProductsByCategory(categoryName).enqueue(new Callback<ProductListResponse>() {
            @Override
            public void onResponse(Call<ProductListResponse> call, Response<ProductListResponse> response) {
                if (response.isSuccessful() && response.body() != null) {
                    List<ProductDto> products = response.body().getItems();
                    productAdapter = new ProductAdapter(CategoryActivity.this, products);
                    recyclerProductGrid.setAdapter(productAdapter);
                } else {
                    Toast.makeText(CategoryActivity.this, "Không có sản phẩm trong danh mục này!", Toast.LENGTH_SHORT).show();
                    recyclerProductGrid.setAdapter(null);
                }
            }

            @Override
            public void onFailure(Call<ProductListResponse> call, Throwable t) {
                Toast.makeText(CategoryActivity.this, "Lỗi kết nối: " + t.getMessage(), Toast.LENGTH_SHORT).show();
            }
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
