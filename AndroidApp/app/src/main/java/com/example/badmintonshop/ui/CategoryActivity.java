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

import com.example.badmintonshop.ui.CartActivity;
import com.example.badmintonshop.R;
import com.example.badmintonshop.adapter.CategoryListAdapter;
import com.example.badmintonshop.adapter.ProductAdapter;
// 🚩 SỬA ĐỔI: Sử dụng ApiClient đã được cải thiện
import com.example.badmintonshop.network.ApiClient;
import com.example.badmintonshop.network.ApiService;
import com.example.badmintonshop.network.dto.CategoryDto;
import com.example.badmintonshop.network.dto.CategoryListResponse;
import com.example.badmintonshop.network.dto.ProductDto;
import com.example.badmintonshop.network.dto.ProductListResponse;
import com.example.badmintonshop.network.dto.ApiResponse;
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
    private CategoryListAdapter categoryAdapter;
    private ApiService api;
    TextView tvSearchBarCategory;
    private BottomNavigationView bottomNav;

    private final Set<Integer> favoriteProductIds = new HashSet<>();

    // Hàm tiện ích kiểm tra trạng thái đăng nhập
    private boolean isLoggedIn() {
        // Nên sử dụng hằng số cho tên file và key để tránh lỗi chính tả
        SharedPreferences sp = getSharedPreferences("auth", MODE_PRIVATE);
        return sp.getInt("customerID", -1) != -1;
    }

    // Hàm tiện ích lấy customerID hiện tại
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

        // 🚩 SỬA ĐỔI: Khởi tạo API một cách nhất quán, không cần truyền baseUrl
        api = ApiClient.getApiService();

        setupProductGrid();
        updateBottomNavLabel();

        // Bắt đầu chuỗi tải dữ liệu: Tải Wishlist -> Tải Danh mục -> Tải Sản phẩm
        loadFavoriteIdsAndThenLoadProducts();

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
                // Đang ở màn hình này, không cần làm gì
                return true;
            } else if (id == R.id.nav_you) {
                if (isLoggedIn()) {
                    showLogoutConfirmDialog();
                } else {
                    startActivity(new Intent(this, LoginActivity.class));
                }
                return true;
            } else if (id == R.id.nav_cart) {
                // 🚩 SỬA ĐỔI: Chuyển đến CartActivity thay vì hiển thị Toast
                startActivity(new Intent(this, CartActivity.class));
                return true;
            }
            return false;
        });
    }

    @Override
    protected void onResume() {
        super.onResume();
        // Cập nhật lại nhãn và danh sách yêu thích khi quay lại màn hình
        updateBottomNavLabel();
        loadFavoriteIdsAndThenLoadProducts(); // Tải lại để cập nhật trạng thái yêu thích
    }

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

    private void loadFavoriteIdsAndThenLoadProducts() {
        if (!isLoggedIn()) {
            favoriteProductIds.clear();
            loadCategories(); // Vẫn tải danh mục để hiển thị
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
                loadCategories();
            }

            @Override
            public void onFailure(Call<WishlistGetResponse> call, Throwable t) {
                loadCategories(); // Dù lỗi vẫn tiếp tục tải UI chính
            }
        });
    }

    private void loadCategories() {
// 🚩 SỬA ĐỔI: Bỏ phần HORIZONTAL đi để nó hiển thị theo chiều dọc mặc định
        recyclerCategoryList.setLayoutManager(new LinearLayoutManager(this));

        api.getCategories().enqueue(new Callback<CategoryListResponse>() {
            @Override
            public void onResponse(Call<CategoryListResponse> call, Response<CategoryListResponse> response) {
                if (response.isSuccessful() && response.body() != null) {
                    List<CategoryDto> categories = response.body().getItems();
                    List<String> categoryNames = new ArrayList<>();
                    categoryNames.add("Featured"); // Thêm mục "Featured"
                    for (CategoryDto c : categories) {
                        categoryNames.add(c.getCategoryName());
                    }

                    categoryAdapter = new CategoryListAdapter(categoryNames, categoryName -> {
                        loadProductsForCategory(categoryName);
                    });
                    recyclerCategoryList.setAdapter(categoryAdapter);

                    // Tải sản phẩm cho danh mục đầu tiên mặc định
                    if (!categoryNames.isEmpty()) {
                        loadProductsForCategory(categoryNames.get(0));
                    }
                } else {
                    Toast.makeText(CategoryActivity.this, "Không tải được danh mục!", Toast.LENGTH_SHORT).show();
                }
            }

            @Override
            public void onFailure(Call<CategoryListResponse> call, Throwable t) {
                Toast.makeText(CategoryActivity.this, "Lỗi kết nối: " + t.getMessage(), Toast.LENGTH_SHORT).show();
            }
        });
    }

    private void setupProductGrid() {
        recyclerProductGrid.setLayoutManager(new GridLayoutManager(this, 2));
    }

    private void loadProductsForCategory(String categoryName) {
        Call<ProductListResponse> call;
        if (categoryName.equalsIgnoreCase("Featured")) {
            // Nếu là "Featured", lấy tất cả sản phẩm
            call = api.getProducts(1, 20);
        } else {
            // Nếu là danh mục cụ thể, lọc theo tên
            call = api.getProductsByCategory(categoryName);
        }

        call.enqueue(new Callback<ProductListResponse>() {
            @Override
            public void onResponse(Call<ProductListResponse> call, Response<ProductListResponse> response) {
                if (response.isSuccessful() && response.body() != null) {
                    updateProductGrid(response.body().getItems());
                } else {
                    Toast.makeText(CategoryActivity.this, "Không có sản phẩm trong danh mục này", Toast.LENGTH_SHORT).show();
                    updateProductGrid(new ArrayList<>()); // Hiển thị danh sách rỗng
                }
            }

            @Override
            public void onFailure(Call<ProductListResponse> call, Throwable t) {
                Toast.makeText(CategoryActivity.this, "Lỗi: " + t.getMessage(), Toast.LENGTH_SHORT).show();
            }
        });
    }

    // 🚩 SỬA ĐỔI: Hàm mới để cập nhật lưới sản phẩm, tránh lặp code
    private void updateProductGrid(List<ProductDto> products) {
        if (products == null) {
            products = new ArrayList<>(); // Đảm bảo không bị null
        }
        ProductAdapter productAdapter = new ProductAdapter(this, products, product -> {
            toggleWishlist(product.getProductID());
        }, favoriteProductIds);
        recyclerProductGrid.setAdapter(productAdapter);
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
                    // Cập nhật lại item cụ thể thay vì toàn bộ adapter sẽ hiệu quả hơn
                    if (recyclerProductGrid.getAdapter() != null) {
                        recyclerProductGrid.getAdapter().notifyDataSetChanged();
                    }
                }
                Toast.makeText(CategoryActivity.this, response.body() != null ? response.body().getMessage() : "Thêm thất bại", Toast.LENGTH_SHORT).show();
            }

            @Override
            public void onFailure(Call<ApiResponse> call, Throwable t) {
                Toast.makeText(CategoryActivity.this, "Lỗi kết nối khi thêm", Toast.LENGTH_LONG).show();
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

            @Override
            public void onFailure(Call<ApiResponse> call, Throwable t) {
                Toast.makeText(CategoryActivity.this, "Lỗi kết nối khi xóa", Toast.LENGTH_LONG).show();
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

        favoriteProductIds.clear(); // Xóa danh sách yêu thích ở local
        updateBottomNavLabel();
        Toast.makeText(this, "Đã đăng xuất", Toast.LENGTH_SHORT).show();

        Intent i = new Intent(this, LoginActivity.class);
        i.setFlags(Intent.FLAG_ACTIVITY_NEW_TASK | Intent.FLAG_ACTIVITY_CLEAR_TASK);
        startActivity(i);
        finish();
    }
}