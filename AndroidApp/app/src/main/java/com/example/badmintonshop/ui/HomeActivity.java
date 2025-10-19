package com.example.badmintonshop.ui;

import android.content.Intent;
import android.content.SharedPreferences;
import android.os.Bundle;
import android.os.Handler;
import android.view.ViewGroup;
import android.widget.ImageView;
import android.widget.TextView;
import android.widget.Toast;
import android.util.Log;

import androidx.appcompat.app.AlertDialog;
import androidx.appcompat.app.AppCompatActivity;
import androidx.recyclerview.widget.RecyclerView;
import androidx.recyclerview.widget.StaggeredGridLayoutManager;
import androidx.viewpager2.widget.ViewPager2;

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

    private static final String TAG = "HomeActivityDebug";
    private static final long BANNER_DELAY_MS = 3000;
    private static final long BANNER_PERIOD_MS = 4000;

    private ApiService api;
    private RecyclerView recyclerMainGrid;
    private TabLayout tabLayout;
    private ViewPager2 bannerSlider;
    private TextView tvSearchBar;
    private ImageView btnFavoriteToolbar;
    private BottomNavigationView bottomNav;

    private final Set<Integer> favoriteProductIds = new HashSet<>();
    private Timer timer;
    private final Handler handler = new Handler();


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
        Log.d(TAG, "onCreate: HomeActivity started.");

        api = ApiClient.getApiService();

        // Ánh xạ view
        recyclerMainGrid = findViewById(R.id.recyclerMainGrid);
        tabLayout = findViewById(R.id.tabLayout);
        bannerSlider = findViewById(R.id.bannerSlider);
        tvSearchBar = findViewById(R.id.tvSearchBar);
        bottomNav = findViewById(R.id.bottomNav);

        // Đảm bảo mục 'Home' luôn được chọn khi Activity khởi tạo
        if (bottomNav.getSelectedItemId() != R.id.nav_home) {
            bottomNav.setSelectedItemId(R.id.nav_home);
        }

        AppBarLayout appBarLayout = findViewById(R.id.appBarLayout);
        ViewGroup toolbarContainer = (ViewGroup) appBarLayout.getChildAt(0);
        btnFavoriteToolbar = toolbarContainer.findViewById(R.id.btnFavoriteToolbar);

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
        loadBanners();
        updateBottomNavLabel();
        // Lần tải đầu tiên
        loadFavoriteIdsAndProducts();

        tvSearchBar.setOnClickListener(v -> startActivity(new Intent(HomeActivity.this, SearchActivity.class)));

        bottomNav.setOnItemSelectedListener(item -> {
            int id = item.getItemId();
            if (id == R.id.nav_home) {
                return true;
            } else if (id == R.id.nav_categories) {
                startActivity(new Intent(this, CategoryActivity.class));
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
        Log.d(TAG, "onResume: Activity resumed. Reloading data.");
        // Đảm bảo mục 'Home' vẫn được chọn khi quay lại
        bottomNav.setSelectedItemId(R.id.nav_home);
        updateBottomNavLabel();
        loadFavoriteIdsAndProducts();
        startBannerAutoScroll(); // Bắt đầu lại auto-scroll
    }

    @Override
    protected void onPause() {
        super.onPause();
        stopBannerAutoScroll(); // Dừng auto-scroll khi Activity không hiển thị
    }

    // Hàm khởi tạo/dừng Auto-scroll
    private void startBannerAutoScroll() {
        if (timer != null) return;
        Log.d(TAG, "startBannerAutoScroll: Starting auto-scroll.");

        timer = new Timer();
        timer.schedule(new TimerTask() {
            @Override
            public void run() {
                handler.post(() -> {
                    if (bannerSlider.getAdapter() == null || bannerSlider.getAdapter().getItemCount() == 0) return;

                    int currentItem = bannerSlider.getCurrentItem();
                    int nextItem = (currentItem + 1) % bannerSlider.getAdapter().getItemCount();
                    bannerSlider.setCurrentItem(nextItem, true);
                });
            }
        }, BANNER_DELAY_MS, BANNER_PERIOD_MS);
    }

    private void stopBannerAutoScroll() {
        if (timer != null) {
            timer.cancel();
            timer = null;
            Log.d(TAG, "stopBannerAutoScroll: Timer cancelled.");
        }
    }


    private void updateBottomNavLabel() {
        SharedPreferences sp = getSharedPreferences("auth", MODE_PRIVATE);
        String customerName = sp.getString("fullName", null);

        if (customerName != null && !customerName.isEmpty()) {
            String shortName = customerName.split(" ")[0];
            bottomNav.getMenu().findItem(R.id.nav_you).setTitle(shortName);
            Log.d(TAG, "BottomNav label updated to: " + shortName);
        } else {
            bottomNav.getMenu().findItem(R.id.nav_you).setTitle("You");
            Log.d(TAG, "BottomNav label set to 'You' (Logged out).");
        }
    }

    // ⭐ SỬA ĐỔI: Tách biệt tải ID yêu thích và tải sản phẩm
    private void loadFavoriteIdsAndProducts() {
        Log.d(TAG, "loadFavoriteIdsAndProducts: Starting fetch. LoggedIn: " + isLoggedIn());
        if (!isLoggedIn()) {
            favoriteProductIds.clear();
            loadProducts("All");
            return;
        }

        int customerId = getCurrentCustomerId();
        Log.d(TAG, "loadFavoriteIdsAndProducts: Fetching wishlist for CustomerID: " + customerId);

        api.getWishlist(customerId).enqueue(new Callback<WishlistGetResponse>() {
            @Override
            public void onResponse(Call<WishlistGetResponse> call, Response<WishlistGetResponse> response) {
                Log.d(TAG, "Wishlist API Response Code: " + response.code());

                if (response.isSuccessful() && response.body() != null && response.body().isSuccess()) {
                    favoriteProductIds.clear();
                    List<ProductDto> wishlist = response.body().getWishlist();
                    int count = wishlist != null ? wishlist.size() : 0;
                    Log.i(TAG, "Wishlist fetched successfully. Found " + count + " items.");

                    if (wishlist != null) {
                        for (ProductDto p : wishlist) {
                            favoriteProductIds.add(p.getProductID());
                        }
                    }
                } else {
                    String msg = response.body() != null ? response.body().getMessage() : "HTTP " + response.code();
                    Log.e(TAG, "Failed to load wishlist IDs: " + msg);
                    favoriteProductIds.clear();
                }
                // Sau khi tải xong IDs (dù thành công hay thất bại), tải sản phẩm
                int selectedTabPosition = tabLayout.getSelectedTabPosition();
                String category = tabLayout.getTabAt(selectedTabPosition).getText().toString();
                Log.d(TAG, "Calling loadProducts after wishlist check for category: " + category);
                loadProducts(category);
            }
            @Override
            public void onFailure(Call<WishlistGetResponse> call, Throwable t) {
                Log.e(TAG, "Wishlist fetch network error: " + t.getMessage(), t);
                favoriteProductIds.clear(); // Mất kết nối thì không có ID yêu thích
                loadProducts("All");
            }
        });
    }

    private void setupTabs() {
        tabLayout.addTab(tabLayout.newTab().setText("All"));
        tabLayout.addTab(tabLayout.newTab().setText("Vợt cầu lông"));
        tabLayout.addTab(tabLayout.newTab().setText("Giày cầu lông"));
        tabLayout.addTab(tabLayout.newTab().setText("Quần áo cầu lông"));
        tabLayout.addTab(tabLayout.newTab().setText("Phụ kiện"));
        Log.d(TAG, "setupTabs: Tabs initialized.");

        tabLayout.addOnTabSelectedListener(new TabLayout.OnTabSelectedListener() {
            @Override
            public void onTabSelected(TabLayout.Tab tab) {
                Log.d(TAG, "onTabSelected: Loading products for category: " + tab.getText());
                loadProducts(tab.getText().toString());
            }
            @Override public void onTabUnselected(TabLayout.Tab tab) {}
            @Override public void onTabReselected(TabLayout.Tab tab) {}
        });
    }

    private void loadProducts(String category) {
        Call<ProductListResponse> call;

        Log.d(TAG, "loadProducts: Preparing to fetch for category: " + category);

        if (category.equalsIgnoreCase("All")) {
            call = api.getProducts(1, 40);
            Log.d(TAG, "loadProducts: Calling API getProducts(page=1, limit=40)");
        } else {
            call = api.getProductsByCategory(category);
            Log.d(TAG, "loadProducts: Calling API getProductsByCategory(category=" + category + ")");
        }

        call.enqueue(new Callback<ProductListResponse>() {
            @Override
            public void onResponse(Call<ProductListResponse> call, Response<ProductListResponse> response) {
                Log.d(TAG, "Products API Response Code: " + response.code());

                if (response.isSuccessful() && response.body() != null && response.body().isSuccess()) {
                    List<ProductDto> products = response.body().getItems();
                    int count = products != null ? products.size() : 0;
                    Log.i(TAG, "Products fetched successfully. Count: " + count);

                    updateProductGrid(products);
                } else {
                    String msg = response.body() != null ? response.body().getMessage() : "HTTP " + response.code();
                    Log.e(TAG, "Failed to load products for category " + category + ". Message: " + msg);
                    Toast.makeText(HomeActivity.this, "Không tải được sản phẩm", Toast.LENGTH_SHORT).show();
                    updateProductGrid(new ArrayList<>());
                }
            }
            @Override
            public void onFailure(Call<ProductListResponse> call, Throwable t) {
                Log.e(TAG, "Product fetch network error: " + t.getMessage(), t);
                Toast.makeText(HomeActivity.this, "Lỗi kết nối mạng", Toast.LENGTH_LONG).show();
                updateProductGrid(new ArrayList<>());
            }
        });
    }

    private void updateProductGrid(List<ProductDto> products) {
        int count = products != null ? products.size() : 0;
        Log.d(TAG, "updateProductGrid: Displaying " + count + " products.");

        if (products == null || products.isEmpty()) {
            recyclerMainGrid.setAdapter(null);
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
                Log.d(TAG, "Banner API Response Code: " + response.code());
                if (response.isSuccessful() && response.body() != null) {
                    List<SliderDto> banners = response.body();
                    Log.i(TAG, "Banners loaded. Count: " + banners.size());

                    bannerSlider.setAdapter(new BannerAdapter(HomeActivity.this, banners));
                    startBannerAutoScroll();
                }
            }
            @Override public void onFailure(Call<List<SliderDto>> call, Throwable t) {
                Log.e(TAG, "Banner fetch error: " + t.getMessage(), t);
            }
        });
    }

    private void toggleWishlist(int productId) {
        if (!isLoggedIn()) {
            Toast.makeText(this, "Vui lòng đăng nhập để sử dụng wishlist", Toast.LENGTH_SHORT).show();
            startActivity(new Intent(HomeActivity.this, LoginActivity.class));
            return;
        }

        if (favoriteProductIds.contains(productId)) {
            Log.d(TAG, "toggleWishlist: Deleting productID " + productId);
            deleteFromWishlist(getCurrentCustomerId(), productId);
        } else {
            Log.d(TAG, "toggleWishlist: Adding productID " + productId);
            addToWishlist(getCurrentCustomerId(), productId);
        }
    }

    private void addToWishlist(int customerId, int productId) {
        api.addToWishlist(new WishlistAddRequest(customerId, productId)).enqueue(new Callback<ApiResponse>() {
            @Override
            public void onResponse(Call<ApiResponse> call, Response<ApiResponse> response) {
                Log.d(TAG, "Add Wishlist Response Code: " + response.code());
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
            @Override public void onFailure(Call<ApiResponse> call, Throwable t) {
                Log.e(TAG, "Add Wishlist network error: " + t.getMessage());
                Toast.makeText(HomeActivity.this, "Lỗi kết nối khi thêm SP yêu thích", Toast.LENGTH_SHORT).show();
            }
        });
    }

    private void deleteFromWishlist(int customerId, int productId) {
        api.deleteFromWishlist(new WishlistDeleteRequest(customerId, productId)).enqueue(new Callback<ApiResponse>() {
            @Override
            public void onResponse(Call<ApiResponse> call, Response<ApiResponse> response) {
                Log.d(TAG, "Delete Wishlist Response Code: " + response.code());
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
            @Override public void onFailure(Call<ApiResponse> call, Throwable t) {
                Log.e(TAG, "Delete Wishlist network error: " + t.getMessage());
                Toast.makeText(HomeActivity.this, "Lỗi kết nối khi xóa SP yêu thích", Toast.LENGTH_SHORT).show();
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