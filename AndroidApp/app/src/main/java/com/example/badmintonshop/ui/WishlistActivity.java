package com.example.badmintonshop.ui;

import android.content.SharedPreferences;
import android.os.Bundle;
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

import java.util.HashSet;
import java.util.List;
import java.util.Set;

import retrofit2.Call;
import retrofit2.Callback;
import retrofit2.Response;

public class WishlistActivity extends AppCompatActivity {

    private ApiService api;
    private RecyclerView recyclerViewWishlist;

    // 🚩 BỎ: không cần BASE_URL ở đây nữa

    private int getCurrentCustomerId() {
        SharedPreferences sp = getSharedPreferences("auth", MODE_PRIVATE);
        return sp.getInt("customerID", -1);
    }

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_wishlist);

        // 🚩 SỬA ĐỔI: Khởi tạo ApiService một cách nhất quán
        api = ApiClient.getApiService();

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
                    Toast.makeText(WishlistActivity.this, response.body().getMessage(), Toast.LENGTH_SHORT).show();
                    if (response.body().isSuccess()) {
                        // Tải lại danh sách để cập nhật UI
                        loadWishlist();
                    }
                } else {
                    Toast.makeText(WishlistActivity.this, "Lỗi khi xóa", Toast.LENGTH_LONG).show();
                }
            }

            @Override
            public void onFailure(Call<ApiResponse> call, Throwable t) {
                Toast.makeText(WishlistActivity.this, "Lỗi kết nối khi xóa: " + t.getMessage(), Toast.LENGTH_LONG).show();
            }
        });
    }

    private void loadWishlist() {
        int customerId = getCurrentCustomerId();

        if (customerId == -1) {
            Toast.makeText(this, "Bạn chưa đăng nhập.", Toast.LENGTH_LONG).show();
            return;
        }

        api.getWishlist(customerId).enqueue(new Callback<WishlistGetResponse>() {
            @Override
            public void onResponse(Call<WishlistGetResponse> call, Response<WishlistGetResponse> response) {
                if (response.isSuccessful() && response.body() != null && response.body().isSuccess()) {
                    List<ProductDto> wishlist = response.body().getWishlist();

                    if (wishlist != null && !wishlist.isEmpty()) {
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
                    Toast.makeText(WishlistActivity.this, "Không tải được wishlist.", Toast.LENGTH_SHORT).show();
                }
            }

            @Override
            public void onFailure(Call<WishlistGetResponse> call, Throwable t) {
                Toast.makeText(WishlistActivity.this, "Lỗi kết nối: " + t.getMessage(), Toast.LENGTH_LONG).show();
            }
        });
    }
}