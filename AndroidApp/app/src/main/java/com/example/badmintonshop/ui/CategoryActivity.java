package com.example.badmintonshop.ui;

import android.content.Intent;
import android.content.SharedPreferences;
import android.os.Bundle;
import android.util.Log;
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
import com.example.badmintonshop.network.dto.BrandDto;
import com.example.badmintonshop.network.dto.BrandListResponse;
import com.example.badmintonshop.network.dto.CategoryDto;
import com.example.badmintonshop.network.dto.CategoryListResponse;
import com.example.badmintonshop.network.dto.CategoryListResponse;
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
import java.util.concurrent.atomic.AtomicInteger;

import retrofit2.Call;
import retrofit2.Callback;
import retrofit2.Response;

public class CategoryActivity extends AppCompatActivity {

    private static final String TAG = "CategoryActivity";
    private RecyclerView recyclerCategoryList, recyclerProductGrid;
    private FilterAdapter filterAdapter;
    private ApiService api;
    private TextView tvSearchBarCategory;
    private BottomNavigationView bottomNav;

    private final Set<Integer> favoriteProductIds = new HashSet<>();
    private final List<FilterItem> filterItems = new ArrayList<>();

    // ⭐ Biến để đồng bộ 2 lệnh gọi API
    private final AtomicInteger loadCounter = new AtomicInteger(2);
    private final List<FilterItem> categoryFilterItems = new ArrayList<>();
    private final List<FilterItem> brandFilterItems = new ArrayList<>();

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

        // ⭐ THAY ĐỔI: Tải bộ lọc động
        loadDynamicFilters();

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
        // Chỉ tải lại sản phẩm, không cần tải lại bộ lọc
        loadFavoriteIdsAndThenLoadProducts();
    }

    // --- CÁC HÀM THIẾT LẬP VÀ TẢI DỮ LIỆU ---

    /**
     * ⭐ HÀM MỚI: Tải động Category và Brand
     */
    private void loadDynamicFilters() {
        Log.d(TAG, "loadDynamicFilters: Starting to fetch categories and brands...");
        filterItems.clear();
        categoryFilterItems.clear();
        brandFilterItems.clear();
        loadCounter.set(2); // Reset bộ đếm về 2

        // 1. Tải Categories
        api.getCategories().enqueue(new Callback<CategoryListResponse>() {
            @Override
            public void onResponse(Call<CategoryListResponse> call, Response<CategoryListResponse> response) {
                if (response.isSuccessful() && response.body() != null && response.body().isSuccess()) {
                    Log.i(TAG, "Categories loaded successfully.");
                    buildCategoryFilters(response.body().getItems());
                } else {
                    Log.e(TAG, "Failed to load categories, using fallback.");
                    buildCategoryFilters(null); // Sử dụng fallback
                }
                checkIfFiltersReady(); // Kiểm tra bộ đếm
            }

            @Override
            public void onFailure(Call<CategoryListResponse> call, Throwable t) {
                Log.e(TAG, "Network error loading categories, using fallback.", t);
                buildCategoryFilters(null); // Sử dụng fallback
                checkIfFiltersReady(); // Kiểm tra bộ đếm
            }
        });

        // 2. Tải Brands
        api.getBrands().enqueue(new Callback<BrandListResponse>() {
            @Override
            public void onResponse(Call<BrandListResponse> call, Response<BrandListResponse> response) {
                if (response.isSuccessful() && response.body() != null && response.body().isSuccess()) {
                    Log.i(TAG, "Brands loaded successfully.");
                    buildBrandFilters(response.body().getItems());
                } else {
                    Log.e(TAG, "Failed to load brands, using fallback.");
                    buildBrandFilters(null); // Sử dụng fallback
                }
                checkIfFiltersReady(); // Kiểm tra bộ đếm
            }

            @Override
            public void onFailure(Call<BrandListResponse> call, Throwable t) {
                Log.e(TAG, "Network error loading brands, using fallback.", t);
                buildBrandFilters(null); // Sử dụng fallback
                checkIfFiltersReady(); // Kiểm tra bộ đếm
            }
        });
    }

    /**
     * ⭐ HÀM MỚI: Xây dựng danh sách filter cho Category
     */
    private void buildCategoryFilters(List<CategoryDto> categories) {
        categoryFilterItems.add(new FilterHeader("Danh mục"));
        // "Featured" là một mục "ảo" không có trong DB
        FilterOption featured = new FilterOption("Featured");
        featured.isSelected = true; // Chọn "Featured" mặc định
        categoryFilterItems.add(featured);

        if (categories != null && !categories.isEmpty()) {
            for (CategoryDto category : categories) {
                categoryFilterItems.add(new FilterOption(category.getCategoryName()));
            }
        } else {
            // Fallback nếu API lỗi
            categoryFilterItems.add(new FilterOption("Vợt cầu lông"));
            categoryFilterItems.add(new FilterOption("Giày cầu lông"));
            categoryFilterItems.add(new FilterOption("Quần áo cầu lông"));
            categoryFilterItems.add(new FilterOption("Phụ kiện"));
        }
    }

    /**
     * ⭐ HÀM MỚI: Xây dựng danh sách filter cho Brand
     */
    private void buildBrandFilters(List<BrandDto> brands) {
        brandFilterItems.add(new FilterHeader("Thương hiệu"));
        // "Tất cả" là một mục "ảo"
        FilterOption allBrands = new FilterOption("Tất cả");
        allBrands.isSelected = true; // Chọn "Tất cả" mặc định
        brandFilterItems.add(allBrands);

        if (brands != null && !brands.isEmpty()) {
            for (BrandDto brand : brands) {
                brandFilterItems.add(new FilterOption(brand.getBrandName()));
            }
        } else {
            // Fallback nếu API lỗi
            brandFilterItems.add(new FilterOption("Yonex"));
            brandFilterItems.add(new FilterOption("Lining"));
            brandFilterItems.add(new FilterOption("Victor"));
            brandFilterItems.add(new FilterOption("Mizuno"));
        }
    }

    /**
     * ⭐ HÀM MỚI: Kiểm tra khi nào cả 2 API cùng xong
     */
    private void checkIfFiltersReady() {
        if (loadCounter.decrementAndGet() == 0) {
            // Cả 2 API đã chạy xong (thành công hoặc thất bại)
            Log.d(TAG, "Both APIs finished. Finalizing filter setup.");

            // Gộp tất cả lại theo đúng thứ tự
            filterItems.addAll(categoryFilterItems);
            filterItems.addAll(brandFilterItems);

            // Thêm bộ lọc "Giá" (cố định)
            filterItems.add(new FilterHeader("Giá"));
            FilterOption allPrices = new FilterOption("Tất cả");
            allPrices.isSelected = true; // Chọn "Tất cả" mặc định
            filterItems.add(allPrices);
            filterItems.add(new FilterOption("Dưới 1 triệu"));
            filterItems.add(new FilterOption("1 - 2 triệu"));
            filterItems.add(new FilterOption("2 - 4 triệu"));
            filterItems.add(new FilterOption("Trên 4 triệu"));

            // Bây giờ mới thiết lập Adapter
            setupFilterAdapter();

            // Và bây giờ mới tải sản phẩm lần đầu
            loadFavoriteIdsAndThenLoadProducts();
        }
    }

    /**
     * ⭐ HÀM MỚI: Tách phần setup adapter ra
     */
    private void setupFilterAdapter() {
        recyclerCategoryList.setLayoutManager(new LinearLayoutManager(this));
        filterAdapter = new FilterAdapter(filterItems, () -> {
            // Khi người dùng thay đổi bộ lọc, gọi lại hàm loadProducts
            // (Không cần tải lại wishlist IDs, chỉ cần tải lại sản phẩm)
            Log.d(TAG, "Filter changed by user. Reloading products...");
            loadProducts();
        });
        recyclerCategoryList.setAdapter(filterAdapter);
    }


    private void loadFavoriteIdsAndThenLoadProducts() {
        // Đảm bảo filterAdapter đã được khởi tạo
        if (filterAdapter == null) {
            Log.w(TAG, "loadFavoriteIds: FilterAdapter not ready. Aborting.");
            return; // Chờ cho loadDynamicFilters() chạy xong
        }

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
                Log.e(TAG, "Failed to load wishlist IDs", t);
                favoriteProductIds.clear();
                // Tiếp tục tải sản phẩm ngay cả khi thất bại
                loadProducts();
            }
        });
    }

    // 🚩 Hàm tải sản phẩm dựa trên các bộ lọc đã chọn
    private void loadProducts() {
        // Đảm bảo filterAdapter đã được khởi tạo
        if (filterAdapter == null) {
            Log.w(TAG, "loadProducts: FilterAdapter not ready. Aborting.");
            return;
        }

        String category = filterAdapter.getSelectedFilterValue("Danh mục");
        String brand = filterAdapter.getSelectedFilterValue("Thương hiệu");
        String priceRange = filterAdapter.getSelectedFilterValue("Giá");

        // "Featured" và "Tất cả" nghĩa là không lọc (gửi null)
        String apiCategory = (category != null && category.equals("Featured")) ? null : category;
        String apiBrand = (brand != null && brand.equals("Tất cả")) ? null : brand;

        // Chuyển đổi khoảng giá thành min/max
        Integer priceMin = null;
        Integer priceMax = null;
        if (priceRange != null) {
            switch (priceRange) {
                case "Dưới 1 triệu": priceMax = 1000000; break;
                case "1 - 2 triệu": priceMin = 1000000; priceMax = 2000000; break;
                case "2 - 4 triệu": priceMin = 2000000; priceMax = 4000000; break;
                case "Trên 4 triệu": priceMin = 4000000; break;
                // "Tất cả" sẽ để cả 2 là null
            }
        }

        Log.d(TAG, "loadProducts: Calling filterProducts API with:" +
                " C=" + apiCategory + ", B=" + apiBrand + ", P_Min=" + priceMin + ", P_Max=" + priceMax);

        // Gọi API filter mới với các tham số
        api.filterProducts(apiCategory, apiBrand, priceMin, priceMax).enqueue(new Callback<ProductListResponse>() {
            @Override
            public void onResponse(Call<ProductListResponse> call, Response<ProductListResponse> response) {
                if (response.isSuccessful() && response.body() != null && response.body().isSuccess()) {
                    updateProductGrid(response.body().getItems());
                } else {
                    String msg = response.body() != null ? response.body().getMessage() : "HTTP " + response.code();
                    Log.e(TAG, "Failed to load products: " + msg);
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
                if (response.isSuccessful() && response.body() != null) {
                    if (response.body().isSuccess()) {
                        favoriteProductIds.add(productId);
                        if (recyclerProductGrid.getAdapter() != null) {
                            recyclerProductGrid.getAdapter().notifyDataSetChanged();
                        }
                    }
                    Toast.makeText(CategoryActivity.this, response.body().getMessage(), Toast.LENGTH_SHORT).show();
                } else {
                    Toast.makeText(CategoryActivity.this, "Thêm thất bại", Toast.LENGTH_SHORT).show();
                }
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
                if (response.isSuccessful() && response.body() != null) {
                    if (response.body().isSuccess()) {
                        favoriteProductIds.remove(productId);
                        if (recyclerProductGrid.getAdapter() != null) {
                            recyclerProductGrid.getAdapter().notifyDataSetChanged();
                        }
                    }
                    Toast.makeText(CategoryActivity.this, response.body().getMessage(), Toast.LENGTH_SHORT).show();
                } else {
                    Toast.makeText(CategoryActivity.this, "Xóa thất bại", Toast.LENGTH_SHORT).show();
                }
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