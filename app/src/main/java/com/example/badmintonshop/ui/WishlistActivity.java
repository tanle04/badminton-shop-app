package com.example.badmintonshop.ui;

import android.content.SharedPreferences;
import android.os.Bundle;
import android.util.Log; // Thêm Log
import android.view.MenuItem;
import android.widget.Toast;

import androidx.annotation.NonNull;
import androidx.appcompat.app.AppCompatActivity;
import androidx.recyclerview.widget.RecyclerView;
import androidx.recyclerview.widget.StaggeredGridLayoutManager;

import com.example.badmintonshop.R;
import com.example.badmintonshop.adapter.ProductAdapter;
import com.example.badmintonshop.network.ApiClient;
import com.example.badmintonshop.network.ApiService;
import com.example.badmintonshop.network.dto.ApiResponse;
import com.example.badmintonshop.network.dto.WishlistGetResponse;
import com.example.badmintonshop.network.dto.WishlistDeleteRequest;
import com.example.badmintonshop.network.dto.ProductDto;

import java.util.ArrayList;
import java.util.HashSet;
import java.util.List;
import java.util.Set;

import retrofit2.Call;
import retrofit2.Callback;
import retrofit2.Response;

public class WishlistActivity extends AppCompatActivity {

    private static final String TAG = "WishlistActivityDebug";
    private ApiService api;
    private RecyclerView recyclerViewWishlist;

    private int getCurrentCustomerId() {
        SharedPreferences sp = getSharedPreferences("auth", MODE_PRIVATE);
        return sp.getInt("customerID", -1);
    }

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_wishlist);

        api = ApiClient.getApiService();

        // Thiết lập Toolbar
        if (getSupportActionBar() != null) {
            getSupportActionBar().setTitle("Sản phẩm yêu thích");
            getSupportActionBar().setDisplayHomeAsUpEnabled(true);
        }

        recyclerViewWishlist = findViewById(R.id.recyclerViewWishlist);
        recyclerViewWishlist.setLayoutManager(
                new StaggeredGridLayoutManager(2, StaggeredGridLayoutManager.VERTICAL)
        );

        loadWishlist();
    }

    @Override
    protected void onResume() {
        super.onResume();
        // Tải lại danh sách mỗi khi quay lại màn hình
        loadWishlist();
    }

    @Override
    public boolean onOptionsItemSelected(@NonNull MenuItem item) {
        if (item.getItemId() == android.R.id.home) {
            finish(); // Đóng Activity hiện tại và quay về màn hình trước đó
            return true;
        }
        return super.onOptionsItemSelected(item);
    }

    private void deleteFromWishlist(int customerId, int productId) {
        WishlistDeleteRequest request = new WishlistDeleteRequest(customerId, productId);

        api.deleteFromWishlist(request).enqueue(new Callback<ApiResponse>() {
            @Override
            public void onResponse(Call<ApiResponse> call, Response<ApiResponse> response) {
                if (response.isSuccessful() && response.body() != null) {
                    if (response.body().isSuccess()) {
                        Toast.makeText(WishlistActivity.this, response.body().getMessage(), Toast.LENGTH_SHORT).show();
                        loadWishlist(); // Tải lại danh sách để cập nhật UI
                    } else {
                        String msg = response.body().getMessage();
                        Toast.makeText(WishlistActivity.this, "Lỗi khi xóa: " + msg, Toast.LENGTH_LONG).show();
                        Log.e(TAG, "Delete failed (Logic): " + msg);
                    }
                } else {
                    Log.e(TAG, "Delete failed (HTTP): " + response.code());
                    Toast.makeText(WishlistActivity.this, "Lỗi phản hồi từ server.", Toast.LENGTH_LONG).show();
                }
            }

            @Override
            public void onFailure(Call<ApiResponse> call, Throwable t) {
                Log.e(TAG, "Delete network error: ", t);
                Toast.makeText(WishlistActivity.this, "Lỗi kết nối: " + t.getMessage(), Toast.LENGTH_LONG).show();
            }
        });
    }

    private void loadWishlist() {
        int customerId = getCurrentCustomerId();

        if (customerId == -1) {
            Toast.makeText(this, "Vui lòng đăng nhập để xem Wishlist.", Toast.LENGTH_LONG).show();
            recyclerViewWishlist.setAdapter(null);
            return;
        }

        api.getWishlist(customerId).enqueue(new Callback<WishlistGetResponse>() {
            @Override
            public void onResponse(Call<WishlistGetResponse> call, Response<WishlistGetResponse> response) {
                if (response.isSuccessful() && response.body() != null && response.body().isSuccess()) {
                    // ⭐ Sửa: Đảm bảo không bị null
                    List<ProductDto> wishlist = response.body().getWishlist();
                    if (wishlist == null) {
                        wishlist = new ArrayList<>();
                    }

                    if (!wishlist.isEmpty()) {
                        // Tạo Set chứa tất cả ID trong danh sách này (tất cả đều là yêu thích)
                        Set<Integer> currentFavoriteIds = new HashSet<>();
                        for (ProductDto p : wishlist) {
                            currentFavoriteIds.add(p.getProductID());
                        }

                        // Truyền Listener để gọi hàm deleteFromWishlist
                        recyclerViewWishlist.setAdapter(
                                new ProductAdapter(
                                        WishlistActivity.this,
                                        wishlist,
                                        product -> {
                                            // Xử lý click tim đỏ -> chỉ có thể là xóa
                                            deleteFromWishlist(customerId, product.getProductID());
                                        },
                                        currentFavoriteIds // Truyền danh sách ID hiện tại
                                )
                        );
                    } else {
                        Toast.makeText(WishlistActivity.this, "Danh sách yêu thích trống.", Toast.LENGTH_LONG).show();
                        recyclerViewWishlist.setAdapter(null); // Xóa danh sách cũ
                    }
                } else {
                    Log.e(TAG, "Load failed (Logic/HTTP): " + response.code());
                    Toast.makeText(WishlistActivity.this, "Không tải được wishlist.", Toast.LENGTH_SHORT).show();
                    recyclerViewWishlist.setAdapter(null);
                }
            }

            @Override
            public void onFailure(Call<WishlistGetResponse> call, Throwable t) {
                Log.e(TAG, "Load network error: ", t);
                Toast.makeText(WishlistActivity.this, "Lỗi kết nối mạng.", Toast.LENGTH_LONG).show();
                recyclerViewWishlist.setAdapter(null);
            }
        });
    }
}