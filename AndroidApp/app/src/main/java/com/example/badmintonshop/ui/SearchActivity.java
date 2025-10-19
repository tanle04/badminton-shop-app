package com.example.badmintonshop.ui;

import android.content.Intent;
import android.content.SharedPreferences;
import android.os.Bundle;
import android.text.Editable;
import android.text.TextWatcher;
import android.util.Log;
import android.widget.EditText;
import android.widget.Toast;

import androidx.appcompat.app.AppCompatActivity;
import androidx.recyclerview.widget.LinearLayoutManager;
import androidx.recyclerview.widget.RecyclerView;
import androidx.recyclerview.widget.StaggeredGridLayoutManager; // Thêm import

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

public class SearchActivity extends AppCompatActivity {

    private static final String TAG = "SearchActivityDebug";
    private EditText edtSearch;
    private RecyclerView recyclerSearch;
    private ApiService api;
    private ProductAdapter adapter;

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
        setContentView(R.layout.activity_search);

        edtSearch = findViewById(R.id.edtSearch);
        recyclerSearch = findViewById(R.id.recyclerSearch);

        // ⭐ SỬA ĐỔI: Chuyển sang StaggeredGridLayoutManager (2 cột)
        recyclerSearch.setLayoutManager(new StaggeredGridLayoutManager(2, StaggeredGridLayoutManager.VERTICAL));

        api = ApiClient.getApiService();

        adapter = new ProductAdapter(
                this,
                new ArrayList<>(),
                product -> toggleWishlist(product.getProductID()),
                favoriteProductIds
        );
        recyclerSearch.setAdapter(adapter);

        loadFavoriteIds();

        edtSearch.addTextChangedListener(new TextWatcher() {
            @Override public void beforeTextChanged(CharSequence s, int start, int count, int after) {}
            @Override public void afterTextChanged(Editable s) {}

            @Override
            public void onTextChanged(CharSequence s, int start, int before, int count) {
                String keyword = s.toString().trim();

                if (keyword.length() >= 2) { // ⭐ TÌM KIẾM TỪ KHI TỪ KHÓA ĐỦ DÀI
                    searchProducts(keyword);
                } else {
                    // Xóa kết quả nếu từ khóa quá ngắn hoặc rỗng
                    adapter.updateData(new ArrayList<>());
                }
            }
        });


    }

    private void searchProducts(String keyword) {
        api.searchProducts(keyword).enqueue(new Callback<ProductListResponse>() {
            @Override
            public void onResponse(Call<ProductListResponse> call, Response<ProductListResponse> response) {
                if (response.isSuccessful() && response.body() != null && response.body().isSuccess()) {
                    adapter.updateData(response.body().getItems());
                } else {
                    // Lỗi HTTP hoặc lỗi logic (isSuccess=false)
                    Log.e(TAG, "Search failed. Code: " + response.code());
                    Toast.makeText(SearchActivity.this, "Không tìm thấy kết quả phù hợp", Toast.LENGTH_SHORT).show();
                    adapter.updateData(new ArrayList<>());
                }
            }

            @Override
            public void onFailure(Call<ProductListResponse> call, Throwable t) {
                Log.e(TAG, "Search network error: ", t);
                Toast.makeText(SearchActivity.this, "Lỗi kết nối mạng", Toast.LENGTH_SHORT).show();
                adapter.updateData(new ArrayList<>());
            }
        });
    }

    // --- LOGIC WISHLIST ---
    private void loadFavoriteIds() {
        if (!isLoggedIn()) {
            favoriteProductIds.clear();
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
            }
            @Override public void onFailure(Call<WishlistGetResponse> call, Throwable t) {
                Log.e(TAG, "Failed to load wishlist IDs: ", t);
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
                    adapter.notifyDataSetChanged();
                }
                Toast.makeText(SearchActivity.this, response.body() != null ? response.body().getMessage() : "Thêm thất bại", Toast.LENGTH_SHORT).show();
            }
            @Override public void onFailure(Call<ApiResponse> call, Throwable t) {
                Toast.makeText(SearchActivity.this, "Lỗi kết nối khi thêm SP yêu thích", Toast.LENGTH_SHORT).show();
            }
        });
    }

    private void deleteFromWishlist(int customerId, int productId) {
        api.deleteFromWishlist(new WishlistDeleteRequest(customerId, productId)).enqueue(new Callback<ApiResponse>() {
            @Override
            public void onResponse(Call<ApiResponse> call, Response<ApiResponse> response) {
                if (response.isSuccessful() && response.body() != null && response.body().isSuccess()) {
                    favoriteProductIds.remove(productId);
                    adapter.notifyDataSetChanged();
                }
                Toast.makeText(SearchActivity.this, response.body() != null ? response.body().getMessage() : "Xóa thất bại", Toast.LENGTH_SHORT).show();
            }
            @Override public void onFailure(Call<ApiResponse> call, Throwable t) {
                Toast.makeText(SearchActivity.this, "Lỗi kết nối khi xóa SP yêu thích", Toast.LENGTH_SHORT).show();
            }
        });
    }
}