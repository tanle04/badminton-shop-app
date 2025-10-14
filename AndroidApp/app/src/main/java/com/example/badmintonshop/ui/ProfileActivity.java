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

import java.util.ArrayList;
import java.util.HashSet;
import java.util.List;
import java.util.Set;

import retrofit2.Call;
import retrofit2.Callback;
import retrofit2.Response;

public class ProfileActivity extends AppCompatActivity {

    private TextView tvFullName;
    private RecyclerView recyclerRecommended;
    private ApiService api;

    // 🚩 NEW: Các biến và hàm để quản lý wishlist
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

        // Lấy và hiển thị tên người dùng
        SharedPreferences sp = getSharedPreferences("auth", MODE_PRIVATE);
        String fullName = sp.getString("fullName", "Guest");
        tvFullName.setText(fullName);

        // Thiết lập sự kiện click
        tvYourOrders.setOnClickListener(v -> Toast.makeText(this, "Chức năng Đơn hàng đang được phát triển", Toast.LENGTH_SHORT).show());
        tvAddresses.setOnClickListener(v -> Toast.makeText(this, "Chức năng Địa chỉ đang được phát triển", Toast.LENGTH_SHORT).show());
        tvLogout.setOnClickListener(v -> showLogoutConfirmDialog());

        // 🚩 MODIFIED: Bắt đầu chuỗi tải dữ liệu đúng cách
        setupRecommendedProducts();
        loadFavoriteIdsAndThenProducts();
    }

    @Override
    protected void onResume() {
        super.onResume();
        // 🚩 NEW: Tải lại dữ liệu khi quay lại màn hình để cập nhật trạng thái tim
        loadFavoriteIdsAndThenProducts();
    }

    private void setupRecommendedProducts() {
        recyclerRecommended.setLayoutManager(new StaggeredGridLayoutManager(2, StaggeredGridLayoutManager.VERTICAL));
    }

    // 🚩 NEW: Tải danh sách ID yêu thích trước, sau đó mới tải sản phẩm
    private void loadFavoriteIdsAndThenProducts() {
        if (!isLoggedIn()) {
            favoriteProductIds.clear();
            loadRecommendedProducts(); // Vẫn tải sản phẩm nhưng không có tim đỏ
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
                // Sau khi có danh sách ID, mới tải sản phẩm
                loadRecommendedProducts();
            }

            @Override
            public void onFailure(Call<WishlistGetResponse> call, Throwable t) {
                // Nếu lỗi, vẫn tải sản phẩm
                loadRecommendedProducts();
            }
        });
    }

    private void loadRecommendedProducts() {
        api.getProducts(1, 10).enqueue(new Callback<ProductListResponse>() {
            @Override
            public void onResponse(Call<ProductListResponse> call, Response<ProductListResponse> response) {
                if (response.isSuccessful() && response.body() != null) {
                    List<ProductDto> products = response.body().getItems();
                    // 🚩 MODIFIED: Khởi tạo Adapter với đầy đủ chức năng wishlist
                    ProductAdapter adapter = new ProductAdapter(
                            ProfileActivity.this,
                            products,
                            product -> toggleWishlist(product.getProductID()), // Listener cho nút tim
                            favoriteProductIds // Truyền danh sách ID yêu thích
                    );
                    recyclerRecommended.setAdapter(adapter);
                }
            }
            @Override public void onFailure(Call<ProductListResponse> call, Throwable t) {}
        });
    }

    // 🚩 NEW: Toàn bộ logic xử lý thêm/xóa wishlist (sao chép từ HomeActivity)
    private void toggleWishlist(int productId) {
        if (!isLoggedIn()) {
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
            @Override public void onFailure(Call<ApiResponse> call, Throwable t) { /* ... */ }
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
            @Override public void onFailure(Call<ApiResponse> call, Throwable t) { /* ... */ }
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

        // 🚩 NEW: Xóa danh sách yêu thích ở local khi đăng xuất
        favoriteProductIds.clear();

        Toast.makeText(this, "Đã đăng xuất", Toast.LENGTH_SHORT).show();

        Intent i = new Intent(this, LoginActivity.class);
        i.setFlags(Intent.FLAG_ACTIVITY_NEW_TASK | Intent.FLAG_ACTIVITY_CLEAR_TASK);
        startActivity(i);
        finish();
    }
}