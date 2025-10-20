package com.example.badmintonshop.ui;

import android.content.Intent;
import android.content.SharedPreferences;
import android.os.Bundle;
import android.widget.TextView;
import android.widget.Toast;
import android.util.Log; // Thêm Log

import androidx.appcompat.app.AlertDialog;
import androidx.appcompat.app.AppCompatActivity;
import androidx.recyclerview.widget.RecyclerView;
import androidx.recyclerview.widget.StaggeredGridLayoutManager;

import com.example.badmintonshop.R;
import com.example.badmintonshop.adapter.ProductAdapter;
import com.example.badmintonshop.network.ApiClient;
import com.example.badmintonshop.network.ApiService;
import com.example.badmintonshop.network.dto.ApiResponse;
import com.example.badmintonshop.network.dto.ProductDto;
import com.example.badmintonshop.network.dto.ProductListResponse;
import com.example.badmintonshop.network.dto.WishlistAddRequest;
import com.example.badmintonshop.network.dto.WishlistDeleteRequest;
import com.example.badmintonshop.network.dto.WishlistGetResponse;
import com.google.android.material.bottomnavigation.BottomNavigationView;

import java.util.ArrayList;
import java.util.HashSet;
import java.util.List;
import java.util.Set;

import retrofit2.Call;
import retrofit2.Callback;
import retrofit2.Response;

public class ProfileActivity extends AppCompatActivity {

    private static final String TAG = "ProfileActivityDebug";

    private TextView tvFullName;
    private RecyclerView recyclerRecommended;
    private ApiService api;
    private BottomNavigationView bottomNav;

    private final Set<Integer> favoriteProductIds = new HashSet<>();

    private boolean isLoggedIn() {
        SharedPreferences sp = getSharedPreferences("auth", MODE_PRIVATE);
        return sp.getInt("customerID", -1) != -1;
    }

    private int getCurrentCustomerId() {
        SharedPreferences sp = getSharedPreferences("auth", MODE_PRIVATE);
        return sp.getInt("customerID", -1);
    }

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_profile);

        api = ApiClient.getApiService();

        // Ánh xạ view
        tvFullName = findViewById(R.id.tvFullName);
        TextView tvYourOrders = findViewById(R.id.tvYourOrders);
        TextView tvAddresses = findViewById(R.id.tvAddresses);
        TextView tvLogout = findViewById(R.id.tvLogout);
        recyclerRecommended = findViewById(R.id.recyclerRecommended);
        bottomNav = findViewById(R.id.bottomNav);

        // Đặt mục "You" được chọn
        bottomNav.setSelectedItemId(R.id.nav_you);

        // Lấy và hiển thị tên người dùng
        SharedPreferences sp = getSharedPreferences("auth", MODE_PRIVATE);
        String fullName = sp.getString("fullName", "Guest");
        tvFullName.setText(fullName);

        // Thiết lập sự kiện click
        tvYourOrders.setOnClickListener(v -> {
            // Kiểm tra đăng nhập trước khi chuyển hướng (nên làm ở mọi chức năng chính)
            if (isLoggedIn()) {
                startActivity(new Intent(ProfileActivity.this, YourOrdersActivity.class));
            } else {
                Toast.makeText(this, "Vui lòng đăng nhập để xem đơn hàng.", Toast.LENGTH_SHORT).show();
                startActivity(new Intent(ProfileActivity.this, LoginActivity.class));
            }
        });
        tvAddresses.setOnClickListener(v -> {
            startActivity(new Intent(this, AddressActivity.class));
        });

        tvLogout.setOnClickListener(v -> showLogoutConfirmDialog());

        // Xử lý sự kiện cho BottomNavigationView
        bottomNav.setOnItemSelectedListener(item -> {
            int id = item.getItemId();
            if (id == R.id.nav_home) {
                // Sử dụng FLAG_ACTIVITY_CLEAR_TOP để quay về HomeActivity đã có trong stack
                Intent intent = new Intent(this, HomeActivity.class);
                intent.setFlags(Intent.FLAG_ACTIVITY_CLEAR_TOP | Intent.FLAG_ACTIVITY_SINGLE_TOP);
                startActivity(intent);
                return true;
            } else if (id == R.id.nav_categories) {
                startActivity(new Intent(this, CategoryActivity.class));
                return true;
            } else if (id == R.id.nav_you) {
                return true;
            } else if (id == R.id.nav_cart) {
                startActivity(new Intent(this, CartActivity.class));
                return true;
            }
            return false;
        });

        setupRecommendedProducts();
    }

    @Override
    protected void onResume() {
        super.onResume();
        // Tải lại để cập nhật danh sách yêu thích
        loadFavoriteIdsAndThenProducts();
    }

    private void setupRecommendedProducts() {
        recyclerRecommended.setLayoutManager(new StaggeredGridLayoutManager(2, StaggeredGridLayoutManager.VERTICAL));
    }

    private void loadFavoriteIdsAndThenProducts() {
        if (!isLoggedIn()) {
            favoriteProductIds.clear();
            loadRecommendedProducts();
            return;
        }

        api.getWishlist(getCurrentCustomerId()).enqueue(new Callback<WishlistGetResponse>() {
            @Override
            public void onResponse(Call<WishlistGetResponse> call, Response<WishlistGetResponse> response) {
                if (response.isSuccessful() && response.body() != null && response.body().isSuccess()) {
                    favoriteProductIds.clear();
                    List<ProductDto> wishlist = response.body().getWishlist();
                    if (wishlist != null) {
                        for (ProductDto p : wishlist) {
                            favoriteProductIds.add(p.getProductID());
                        }
                    }
                }
                loadRecommendedProducts();
            }

            @Override
            public void onFailure(Call<WishlistGetResponse> call, Throwable t) {
                Log.e(TAG, "Wishlist fetch failed: ", t);
                loadRecommendedProducts();
            }
        });
    }

    private void loadRecommendedProducts() {
        // Tải 10 sản phẩm ngẫu nhiên/mới nhất để làm gợi ý
        api.getProducts(1, 10).enqueue(new Callback<ProductListResponse>() {
            @Override
            public void onResponse(Call<ProductListResponse> call, Response<ProductListResponse> response) {
                if (response.isSuccessful() && response.body() != null && response.body().isSuccess()) {
                    ProductAdapter adapter = new ProductAdapter(
                            ProfileActivity.this,
                            response.body().getItems(),
                            product -> toggleWishlist(product.getProductID()),
                            favoriteProductIds
                    );
                    recyclerRecommended.setAdapter(adapter);
                } else {
                    Log.e(TAG, "Failed to load recommended products: " + response.code());
                    recyclerRecommended.setAdapter(null);
                }
            }
            @Override public void onFailure(Call<ProductListResponse> call, Throwable t) {
                Log.e(TAG, "Recommended products network error: ", t);
                Toast.makeText(ProfileActivity.this, "Lỗi kết nối khi tải gợi ý", Toast.LENGTH_SHORT).show();
                recyclerRecommended.setAdapter(null);
            }
        });
    }

    private void toggleWishlist(int productId) {
        if (!isLoggedIn()) {
            Toast.makeText(this, "Vui lòng đăng nhập để sử dụng wishlist", Toast.LENGTH_SHORT).show();
            startActivity(new Intent(this, LoginActivity.class));
            return;
        }
        if (favoriteProductIds.contains(productId)) {
            deleteFromWishlist(getCurrentCustomerId(), productId);
        } else {
            addToWishlist(getCurrentCustomerId(), productId);
        }
    }

    private void addToWishlist(int customerId, int productId) {
        api.addToWishlist(new WishlistAddRequest(customerId, productId)).enqueue(new Callback<ApiResponse>() {
            @Override
            public void onResponse(Call<ApiResponse> call, Response<ApiResponse> response) {
                if (response.isSuccessful() && response.body() != null && response.body().isSuccess()) {
                    favoriteProductIds.add(productId);
                    if (recyclerRecommended.getAdapter() != null) {
                        recyclerRecommended.getAdapter().notifyDataSetChanged();
                    }
                }
                Toast.makeText(ProfileActivity.this, response.body() != null ? response.body().getMessage() : "Thêm thất bại", Toast.LENGTH_SHORT).show();
            }
            @Override public void onFailure(Call<ApiResponse> call, Throwable t) {
                Toast.makeText(ProfileActivity.this, "Lỗi kết nối khi thêm yêu thích", Toast.LENGTH_SHORT).show();
            }
        });
    }

    private void deleteFromWishlist(int customerId, int productId) {
        api.deleteFromWishlist(new WishlistDeleteRequest(customerId, productId)).enqueue(new Callback<ApiResponse>() {
            @Override
            public void onResponse(Call<ApiResponse> call, Response<ApiResponse> response) {
                if (response.isSuccessful() && response.body() != null && response.body().isSuccess()) {
                    favoriteProductIds.remove(productId);
                    if (recyclerRecommended.getAdapter() != null) {
                        recyclerRecommended.getAdapter().notifyDataSetChanged();
                    }
                }
                Toast.makeText(ProfileActivity.this, response.body() != null ? response.body().getMessage() : "Xóa thất bại", Toast.LENGTH_SHORT).show();
            }
            @Override public void onFailure(Call<ApiResponse> call, Throwable t) {
                Toast.makeText(ProfileActivity.this, "Lỗi kết nối khi xóa yêu thích", Toast.LENGTH_SHORT).show();
            }
        });
    }

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

        favoriteProductIds.clear();

        Toast.makeText(this, "Đã đăng xuất", Toast.LENGTH_SHORT).show();

        // Chuyển hướng về màn hình đăng nhập và xóa sạch stack
        Intent i = new Intent(this, LoginActivity.class);
        i.setFlags(Intent.FLAG_ACTIVITY_NEW_TASK | Intent.FLAG_ACTIVITY_CLEAR_TASK);
        startActivity(i);
        finish();
    }
}