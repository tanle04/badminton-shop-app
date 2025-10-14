package com.example.badmintonshop.ui;

import android.content.Intent;
import android.content.SharedPreferences;
import android.os.Bundle;
import android.view.ViewGroup;
import android.widget.ImageView;
import android.widget.TextView;
import android.widget.Toast;

import androidx.appcompat.app.AlertDialog;
import androidx.appcompat.app.AppCompatActivity;
import androidx.recyclerview.widget.RecyclerView;
import androidx.recyclerview.widget.StaggeredGridLayoutManager;
import androidx.viewpager2.widget.ViewPager2;

import com.example.badmintonshop.ui.CartActivity;
import com.example.badmintonshop.R;
import com.example.badmintonshop.adapter.BannerAdapter;
import com.example.badmintonshop.adapter.ProductAdapter;
import com.example.badmintonshop.network.ApiClient;
import com.example.badmintonshop.network.ApiService;
import com.example.badmintonshop.network.dto.ApiResponse;
import com.example.badmintonshop.network.dto.ProductDto;
import com.example.badmintonshop.network.dto.ProductListResponse;
import com.example.badmintonshop.network.dto.SliderDto;
import com.example.badmintonshop.network.dto.WishlistAddRequest;
import com.example.badmintonshop.network.dto.WishlistGetResponse;
import com.example.badmintonshop.network.dto.WishlistDeleteRequest;

import com.google.android.material.appbar.AppBarLayout;
import com.google.android.material.bottomnavigation.BottomNavigationView;
import com.google.android.material.tabs.TabLayout;

import java.util.ArrayList;
import java.util.HashSet;
import java.util.List;
import java.util.Set;
import java.util.Timer;
import java.util.TimerTask;

import retrofit2.Call;
import retrofit2.Callback;
import retrofit2.Response;

public class HomeActivity extends AppCompatActivity {

    // 🚩 BỎ: không cần BASE_URL ở đây nữa, ApiClient sẽ quản lý việc này
    private ApiService api;

    private RecyclerView recyclerMainGrid;
    private TabLayout tabLayout;
    private ViewPager2 bannerSlider;
    private TextView tvSearchBar;
    private ImageView btnFavoriteToolbar;
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
        setContentView(R.layout.activity_home);

        // 🚩 SỬA ĐỔI: Khởi tạo ApiService một cách nhất quán
        api = ApiClient.getApiService();

        // Ánh xạ view
        recyclerMainGrid = findViewById(R.id.recyclerMainGrid);
        tabLayout = findViewById(R.id.tabLayout);
        bannerSlider = findViewById(R.id.bannerSlider);
        tvSearchBar = findViewById(R.id.tvSearchBar);
        bottomNav = findViewById(R.id.bottomNav);
        bottomNav.setSelectedItemId(R.id.nav_home);

        AppBarLayout appBarLayout = findViewById(R.id.appBarLayout);
        ViewGroup toolbarContainer = (ViewGroup) appBarLayout.getChildAt(0);
        btnFavoriteToolbar = toolbarContainer.findViewById(R.id.btnFavoriteToolbar); // Dùng ID sẽ an toàn hơn

        btnFavoriteToolbar.setOnClickListener(v -> {
            if (isLoggedIn()) {
                startActivity(new Intent(HomeActivity.this, WishlistActivity.class));
            } else {
                Toast.makeText(HomeActivity.this, "Vui lòng đăng nhập để xem Wishlist", Toast.LENGTH_SHORT).show();
                startActivity(new Intent(HomeActivity.this, LoginActivity.class));
            }
        });

        recyclerMainGrid.setLayoutManager(new StaggeredGridLayoutManager(2, StaggeredGridLayoutManager.VERTICAL));

        setupTabs();
        loadFavoriteIdsAndProducts();
        loadBanners();
        updateBottomNavLabel();

        tvSearchBar.setOnClickListener(v -> startActivity(new Intent(HomeActivity.this, SearchActivity.class)));

        bottomNav.setOnItemSelectedListener(item -> {
            int id = item.getItemId();
            if (id == R.id.nav_home) {
                return true;
            } else if (id == R.id.nav_categories) {
                startActivity(new Intent(this, CategoryActivity.class));
                return true;
            } else if (id == R.id.nav_you) {
                // 🚩 SỬA ĐỔI: Chuyển sang ProfileActivity thay vì đăng xuất
                if (isLoggedIn()) {
                    startActivity(new Intent(this, ProfileActivity.class));
                } else {
                    startActivity(new Intent(this, LoginActivity.class));
                }
                return true; // Giữ lại return true để mục được chọn
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
        loadFavoriteIdsAndProducts(); // Tải lại để cập nhật trạng thái yêu thích
    }

    private void updateBottomNavLabel() {
        SharedPreferences sp = getSharedPreferences("auth", MODE_PRIVATE);
        // 🚩 SỬA ĐỔI: Lấy tên người dùng với key "fullName" cho đúng
        String customerName = sp.getString("fullName", null);

        if (customerName != null && !customerName.isEmpty()) {
            String shortName = customerName.split(" ")[0];
            bottomNav.getMenu().findItem(R.id.nav_you).setTitle(shortName);
        } else {
            bottomNav.getMenu().findItem(R.id.nav_you).setTitle("You");
        }
    }

    private void loadFavoriteIdsAndProducts() {
        if (!isLoggedIn()) {
            favoriteProductIds.clear();
            loadProducts("All"); // Tải tất cả sản phẩm
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
                // Sau khi tải xong IDs, tải sản phẩm cho tab đang được chọn
                int selectedTabPosition = tabLayout.getSelectedTabPosition();
                String category = tabLayout.getTabAt(selectedTabPosition).getText().toString();
                loadProducts(category);
            }
            @Override
            public void onFailure(Call<WishlistGetResponse> call, Throwable t) {
                loadProducts("All"); // Nếu lỗi, vẫn tải sản phẩm
            }
        });
    }

    private void setupTabs() {
        tabLayout.addTab(tabLayout.newTab().setText("All"));
        tabLayout.addTab(tabLayout.newTab().setText("Vợt cầu lông"));
        tabLayout.addTab(tabLayout.newTab().setText("Giày cầu lông"));
        tabLayout.addTab(tabLayout.newTab().setText("Quần áo cầu lông"));
        tabLayout.addTab(tabLayout.newTab().setText("Phụ kiện"));

        tabLayout.addOnTabSelectedListener(new TabLayout.OnTabSelectedListener() {
            @Override
            public void onTabSelected(TabLayout.Tab tab) {
                loadProducts(tab.getText().toString());
            }
            @Override public void onTabUnselected(TabLayout.Tab tab) {}
            @Override public void onTabReselected(TabLayout.Tab tab) {}
        });
    }

    // 🚩 SỬA ĐỔI: Gộp logic tải sản phẩm vào một hàm duy nhất
    private void loadProducts(String category) {
        Call<ProductListResponse> call;
        if (category.equalsIgnoreCase("All")) {
            call = api.getProducts(1, 40);
        } else {
            call = api.getProductsByCategory(category);
        }

        call.enqueue(new Callback<ProductListResponse>() {
            @Override
            public void onResponse(Call<ProductListResponse> call, Response<ProductListResponse> response) {
                if (response.isSuccessful() && response.body() != null) {
                    List<ProductDto> products = response.body().getItems();
                    updateProductGrid(products);
                } else {
                    Toast.makeText(HomeActivity.this, "Không tải được sản phẩm", Toast.LENGTH_SHORT).show();
                    updateProductGrid(new ArrayList<>()); // Hiển thị lưới rỗng
                }
            }
            @Override
            public void onFailure(Call<ProductListResponse> call, Throwable t) {
                Toast.makeText(HomeActivity.this, "Lỗi kết nối: " + t.getMessage(), Toast.LENGTH_LONG).show();
            }
        });
    }

    // 🚩 NEW: Hàm riêng để cập nhật lưới sản phẩm, tránh lặp code
    private void updateProductGrid(List<ProductDto> products) {
        if (products == null || products.isEmpty()) {
            Toast.makeText(this, "Không có sản phẩm nào", Toast.LENGTH_SHORT).show();
            recyclerMainGrid.setAdapter(null); // Xóa adapter cũ
            return;
        }

        ProductAdapter adapter = new ProductAdapter(this, products, product -> {
            toggleWishlist(product.getProductID());
        }, favoriteProductIds);
        recyclerMainGrid.setAdapter(adapter);
    }

    private void loadBanners() {
        api.getSliders().enqueue(new Callback<List<SliderDto>>() {
            @Override
            public void onResponse(Call<List<SliderDto>> call, Response<List<SliderDto>> response) {
                if (response.isSuccessful() && response.body() != null) {
                    List<SliderDto> banners = response.body();
                    bannerSlider.setAdapter(new BannerAdapter(HomeActivity.this, banners));

                    // Auto-scroll logic... (giữ nguyên)
                }
            }
            @Override public void onFailure(Call<List<SliderDto>> call, Throwable t) {}
        });
    }

    private void toggleWishlist(int productId) {
        if (!isLoggedIn()) {
            Toast.makeText(this, "Vui lòng đăng nhập để sử dụng wishlist", Toast.LENGTH_SHORT).show();
            startActivity(new Intent(HomeActivity.this, LoginActivity.class));
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
                        if (recyclerMainGrid.getAdapter() != null) {
                            recyclerMainGrid.getAdapter().notifyDataSetChanged();
                        }
                    }
                    Toast.makeText(HomeActivity.this, response.body().getMessage(), Toast.LENGTH_SHORT).show();
                }
            }
            @Override public void onFailure(Call<ApiResponse> call, Throwable t) { /* ... */ }
        });
    }

    private void deleteFromWishlist(int customerId, int productId) {
        api.deleteFromWishlist(new WishlistDeleteRequest(customerId, productId)).enqueue(new Callback<ApiResponse>() {
            @Override
            public void onResponse(Call<ApiResponse> call, Response<ApiResponse> response) {
                if (response.isSuccessful() && response.body() != null) {
                    if (response.body().isSuccess()) {
                        favoriteProductIds.remove(productId);
                        if (recyclerMainGrid.getAdapter() != null) {
                            recyclerMainGrid.getAdapter().notifyDataSetChanged();
                        }
                    }
                    Toast.makeText(HomeActivity.this, response.body().getMessage(), Toast.LENGTH_SHORT).show();
                }
            }
            @Override public void onFailure(Call<ApiResponse> call, Throwable t) { /* ... */ }
        });
    }

    // Các hàm showLogoutConfirmDialog() và logout() giữ nguyên
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