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
import com.example.badmintonshop.adapter.FilterAdapter;
import com.example.badmintonshop.adapter.ProductAdapter;
import com.example.badmintonshop.model.FilterHeader;
import com.example.badmintonshop.model.FilterItem;
import com.example.badmintonshop.model.FilterOption;
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

public class CategoryActivity extends AppCompatActivity {

    private RecyclerView recyclerCategoryList, recyclerProductGrid;
    private FilterAdapter filterAdapter; // 🚩 SỬA ĐỔI: Dùng FilterAdapter
    private ApiService api;
    private TextView tvSearchBarCategory;
    private BottomNavigationView bottomNav;

    private final Set<Integer> favoriteProductIds = new HashSet<>();
    private final List<FilterItem> filterItems = new ArrayList<>();

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
        setContentView(R.layout.activity_category);

        recyclerCategoryList = findViewById(R.id.recyclerCategoryList);
        recyclerProductGrid = findViewById(R.id.recyclerProductGrid);
        bottomNav = findViewById(R.id.bottomNav);
        tvSearchBarCategory = findViewById(R.id.tvSearchBarCategory);

        api = ApiClient.getApiService();

        setupProductGrid();
        updateBottomNavLabel();

        // 🚩 SỬA ĐỔI: Thiết lập bộ lọc và tải dữ liệu theo luồng mới
        setupFilters();
        loadFavoriteIdsAndThenLoadProducts();

        tvSearchBarCategory.setOnClickListener(v -> startActivity(new Intent(this, SearchActivity.class)));

        bottomNav.setOnItemSelectedListener(item -> {
            int id = item.getItemId();
            if (id == R.id.nav_home) {
                startActivity(new Intent(this, HomeActivity.class));
                return true;
            } else if (id == R.id.nav_categories) {
                return true;
            } else if (id == R.id.nav_you) {
                if (isLoggedIn()) {
                    startActivity(new Intent(this, ProfileActivity.class));
                } else {
                    startActivity(new Intent(this, LoginActivity.class));
                }
                return true;
            } else if (id == R.id.nav_cart) {
                startActivity(new Intent(this, CartActivity.class));
                return true;
            }
            return false;
        });
    }

    @Override
    protected void onResume() {
        super.onResume();
        updateBottomNavLabel();
        loadFavoriteIdsAndThenLoadProducts();
    }

    // --- CÁC HÀM THIẾT LẬP VÀ TẢI DỮ LIỆU ---

    private void setupFilters() {
        // Tạo cấu trúc dữ liệu cho bộ lọc
        filterItems.add(new FilterHeader("Danh mục"));
        filterItems.add(new FilterOption("Featured"));
        filterItems.add(new FilterOption("Vợt cầu lông"));
        filterItems.add(new FilterOption("Giày cầu lông"));
        filterItems.add(new FilterOption("Quần áo cầu lông"));
        filterItems.add(new FilterOption("Phụ kiện"));

        filterItems.add(new FilterHeader("Thương hiệu"));
        filterItems.add(new FilterOption("Tất cả"));
        filterItems.add(new FilterOption("Yonex"));
        filterItems.add(new FilterOption("Lining"));
        filterItems.add(new FilterOption("Victor"));
        filterItems.add(new FilterOption("Mizuno"));

        filterItems.add(new FilterHeader("Giá"));
        filterItems.add(new FilterOption("Tất cả"));
        filterItems.add(new FilterOption("Dưới 1 triệu"));
        filterItems.add(new FilterOption("1 - 2 triệu"));
        filterItems.add(new FilterOption("2 - 4 triệu"));
        filterItems.add(new FilterOption("Trên 4 triệu"));

        // Đặt mục "Featured" và "Tất cả" được chọn mặc định
        ((FilterOption) filterItems.get(1)).isSelected = true;
        ((FilterOption) filterItems.get(7)).isSelected = true;
        ((FilterOption) filterItems.get(13)).isSelected = true;

        recyclerCategoryList.setLayoutManager(new LinearLayoutManager(this));
        filterAdapter = new FilterAdapter(filterItems, () -> {
            // Khi người dùng thay đổi bộ lọc, gọi lại hàm loadProducts
            loadProducts();
        });
        recyclerCategoryList.setAdapter(filterAdapter);
    }

    private void loadFavoriteIdsAndThenLoadProducts() {
        if (!isLoggedIn()) {
            favoriteProductIds.clear();
            loadProducts(); // Vẫn tải sản phẩm với bộ lọc mặc định
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
                loadProducts();
            }

            @Override
            public void onFailure(Call<WishlistGetResponse> call, Throwable t) {
                // Tiếp tục tải sản phẩm ngay cả khi thất bại
                loadProducts();
            }
        });
    }

    // 🚩 Hàm tải sản phẩm dựa trên các bộ lọc đã chọn
    private void loadProducts() {
        String category = filterAdapter.getSelectedFilterValue("Danh mục");
        String brand = filterAdapter.getSelectedFilterValue("Thương hiệu");
        String priceRange = filterAdapter.getSelectedFilterValue("Giá");

        // Chuyển đổi khoảng giá thành min/max
        Integer priceMin = null;
        Integer priceMax = null;
        if (priceRange != null) {
            switch (priceRange) {
                case "Dưới 1 triệu": priceMax = 1000000; break;
                case "1 - 2 triệu": priceMin = 1000000; priceMax = 2000000; break;
                case "2 - 4 triệu": priceMin = 2000000; priceMax = 4000000; break;
                case "Trên 4 triệu": priceMin = 4000000; break;
            }
        }

        // Gọi API filter mới với các tham số
        // category và brand sẽ là null nếu người dùng chọn "Featured" hoặc "Tất cả"
        api.filterProducts(category, brand, priceMin, priceMax).enqueue(new Callback<ProductListResponse>() {
            @Override
            public void onResponse(Call<ProductListResponse> call, Response<ProductListResponse> response) {
                if (response.isSuccessful() && response.body() != null && response.body().isSuccess()) {
                    updateProductGrid(response.body().getItems());
                } else {
                    // Xử lý trường hợp API trả về lỗi logic hoặc HTTP lỗi
                    updateProductGrid(new ArrayList<>());
                    Toast.makeText(CategoryActivity.this, "Không tải được sản phẩm", Toast.LENGTH_SHORT).show();
                }
            }

            @Override
            public void onFailure(Call<ProductListResponse> call, Throwable t) {
                updateProductGrid(new ArrayList<>());
                Toast.makeText(CategoryActivity.this, "Lỗi kết nối: " + t.getMessage(), Toast.LENGTH_SHORT).show();
            }
        });
    }

    private void updateProductGrid(List<ProductDto> products) {
        if (products == null || products.isEmpty()) {
            Toast.makeText(this, "Không có sản phẩm phù hợp", Toast.LENGTH_SHORT).show();
        }
        ProductAdapter productAdapter = new ProductAdapter(this, products, product -> {
            toggleWishlist(product.getProductID());
        }, favoriteProductIds);
        recyclerProductGrid.setAdapter(productAdapter);
    }

    private void setupProductGrid() {
        recyclerProductGrid.setLayoutManager(new GridLayoutManager(this, 2));
    }

    // --- CÁC HÀM CŨ GIỮ NGUYÊN ---
    private void updateBottomNavLabel() {
        SharedPreferences sp = getSharedPreferences("auth", MODE_PRIVATE);
        String customerName = sp.getString("fullName", null);

        if (customerName != null && !customerName.isEmpty()) {
            String shortName = customerName.split(" ")[0];
            bottomNav.getMenu().findItem(R.id.nav_you).setTitle(shortName);
        } else {
            bottomNav.getMenu().findItem(R.id.nav_you).setTitle("You");
        }
    }
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
                    if (recyclerProductGrid.getAdapter() != null) {
                        recyclerProductGrid.getAdapter().notifyDataSetChanged();
                    }
                }
                Toast.makeText(CategoryActivity.this, response.body() != null ? response.body().getMessage() : "Thêm thất bại", Toast.LENGTH_SHORT).show();
            }
            @Override public void onFailure(Call<ApiResponse> call, Throwable t) {
                Toast.makeText(CategoryActivity.this, "Lỗi kết nối khi thêm SP yêu thích", Toast.LENGTH_SHORT).show();
            }
        });
    }

    private void deleteFromWishlist(int customerId, int productId) {
        api.deleteFromWishlist(new WishlistDeleteRequest(customerId, productId)).enqueue(new Callback<ApiResponse>() {
            @Override
            public void onResponse(Call<ApiResponse> call, Response<ApiResponse> response) {
                if (response.isSuccessful() && response.body() != null && response.body().isSuccess()) {
                    favoriteProductIds.remove(productId);
                    if (recyclerProductGrid.getAdapter() != null) {
                        recyclerProductGrid.getAdapter().notifyDataSetChanged();
                    }
                }
                Toast.makeText(CategoryActivity.this, response.body() != null ? response.body().getMessage() : "Xóa thất bại", Toast.LENGTH_SHORT).show();
            }
            @Override public void onFailure(Call<ApiResponse> call, Throwable t) {
                Toast.makeText(CategoryActivity.this, "Lỗi kết nối khi xóa SP yêu thích", Toast.LENGTH_SHORT).show();
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
        updateBottomNavLabel();
        Toast.makeText(this, "Đã đăng xuất", Toast.LENGTH_SHORT).show();
        Intent i = new Intent(this, LoginActivity.class);
        i.setFlags(Intent.FLAG_ACTIVITY_NEW_TASK | Intent.FLAG_ACTIVITY_CLEAR_TASK);
        startActivity(i);
        finish();
    }
}