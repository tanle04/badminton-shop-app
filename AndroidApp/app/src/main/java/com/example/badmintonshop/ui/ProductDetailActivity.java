package com.example.badmintonshop.ui;

import android.app.Dialog;
import android.content.Intent;
import android.content.SharedPreferences;
import android.graphics.Color;
import android.graphics.Paint; // ⭐ IMPORT CẦN THIẾT CHO GẠCH NGANG
import android.os.Bundle;
import android.util.Log;
import android.util.TypedValue; // ⭐ IMPORT CẦN THIẾT CHO SIZE CHỮ
import android.view.View;
import android.widget.ImageButton;
import android.widget.ImageView;
import android.widget.LinearLayout;
import android.widget.RatingBar;
import android.widget.TextView;
import android.widget.Toast;

import androidx.appcompat.app.AppCompatActivity;
import androidx.core.content.ContextCompat;
import androidx.recyclerview.widget.LinearLayoutManager;
import androidx.recyclerview.widget.RecyclerView;
import androidx.viewpager2.widget.ViewPager2;

import com.bumptech.glide.Glide;
import com.bumptech.glide.load.resource.drawable.DrawableTransitionOptions;
import com.example.badmintonshop.R;
import com.example.badmintonshop.adapter.GalleryAdapter;
import com.example.badmintonshop.network.ApiClient;
import com.example.badmintonshop.network.ApiService;
import com.example.badmintonshop.network.dto.ApiResponse;
import com.example.badmintonshop.network.dto.ProductDetailResponse;
import com.example.badmintonshop.network.dto.ProductDto;
import com.example.badmintonshop.network.dto.WishlistAddRequest;
import com.example.badmintonshop.network.dto.WishlistDeleteRequest;
import com.google.android.material.button.MaterialButton;

import java.util.List;
import java.util.Locale;

import retrofit2.Call;
import retrofit2.Callback;
import retrofit2.Response;

public class ProductDetailActivity extends AppCompatActivity {

    private ViewPager2 pagerImages;
    private RecyclerView recyclerGalleryThumbs;
    private TextView txtName, txtPrice, txtDesc, txtImageCount, tvRatingScore;

    // ⭐ ÁNH XẠ TEXTVIEW MỚI CHO GIÁ GỐC TRONG CHI TIẾT
    private TextView tvOriginalPriceDetail;

    private LinearLayout layoutVariants;
    private MaterialButton btnAddToCart;
    private RatingBar ratingBarProductDetail;
    private ImageButton btnWishlist;

    private ImageButton btnDecreaseQuantity, btnIncreaseQuantity;
    private TextView tvQuantity;

    // Các biến thành viên bị thiếu (cho Review Summary)
    private TextView tvAverageRating, tvTotalReviews;
    private RatingBar ratingBarSummary;
    private TextView btnViewAllReviews;

    private ApiService api;
    private int selectedVariantId = -1;
    private int currentQuantity = 1;
    private int currentProductId = -1;
    private String currentProductName = "";
    private boolean isInWishlist = false;

    private ProductDto.VariantDto selectedVariant = null;

    private static final String BASE_IMAGE_URL = "http://10.0.2.2/api/BadmintonShop/images/";
    private int selectedThumb = -1;
    private static final String TAG = "PRODUCT_DETAIL_DEBUG";

    // ⭐ THÊM MÀU VÀ HÀM FORMAT TIỀN TỆ
    private static final int COLOR_SALE = R.color.red_sale;
    private static final int COLOR_DEFAULT = R.color.default_text;
    private static final int COLOR_ORIGINAL = R.color.gray_strikethrough;

    private String formatCurrency(double price) {
        return String.format(Locale.GERMAN, "%,.0f ₫", price);
    }
    // ⭐ KẾT THÚC THÊM MÀU VÀ HÀM FORMAT

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_product_detail);

        // Bind views
        pagerImages = findViewById(R.id.pagerProductImages);
        recyclerGalleryThumbs = findViewById(R.id.recyclerGalleryThumbs);
        txtName = findViewById(R.id.txtProductNameDetail);
        txtPrice = findViewById(R.id.txtProductPriceDetail);
        txtDesc = findViewById(R.id.txtProductDescriptionDetail);
        txtImageCount = findViewById(R.id.txtImageCount);
        layoutVariants = findViewById(R.id.layoutVariants);
        btnAddToCart = findViewById(R.id.btnAddToCart);

        btnDecreaseQuantity = findViewById(R.id.btnDecreaseQuantity);
        tvQuantity = findViewById(R.id.tvQuantity);
        btnIncreaseQuantity = findViewById(R.id.btnIncreaseQuantity);

        // ⭐ BIND TEXTVIEW CHO GIÁ GỐC (ĐÃ SỬA TRONG LAYOUT Ở BƯỚC TRƯỚC)
        tvOriginalPriceDetail = findViewById(R.id.tv_original_price_detail);

        // Bind Views cho phần Rating và Review
        tvRatingScore = findViewById(R.id.tvRatingScore);
        ratingBarProductDetail = findViewById(R.id.ratingBarProductDetail);
        tvAverageRating = findViewById(R.id.tvAverageRating);
        ratingBarSummary = findViewById(R.id.ratingBarSummary);
        tvTotalReviews = findViewById(R.id.tvTotalReviews);
        btnViewAllReviews = findViewById(R.id.btnViewAllReviews);
        btnWishlist = findViewById(R.id.btnWishlist);

        api = ApiClient.getApiService();

        int productID = getIntent().getIntExtra("productID", -1);
        if (productID == -1) {
            Toast.makeText(this, "Không tìm thấy sản phẩm!", Toast.LENGTH_SHORT).show();
            finish();
            return;
        }

        currentProductId = productID;
        loadProductDetail(productID);
        setupQuantityButtons();
        btnAddToCart.setOnClickListener(v -> handleAddToCart());

        btnWishlist.setOnClickListener(v -> handleWishlist());
        btnViewAllReviews.setOnClickListener(v -> handleViewAllReviews());
    }

    // ⭐ HÀM XỬ LÝ WISHLIST (Giữ nguyên)
    private void handleWishlist() {
        int customerId = getCurrentCustomerId();
        if (customerId == -1) {
            Toast.makeText(this, "Vui lòng đăng nhập để thêm vào Wishlist.", Toast.LENGTH_SHORT).show();
            startActivity(new Intent(this, LoginActivity.class));
            return;
        }

        if (isInWishlist) {
            // Xóa khỏi wishlist (Sử dụng Request Body WishlistDeleteRequest)
            WishlistDeleteRequest request = new WishlistDeleteRequest(customerId, currentProductId);
            api.deleteFromWishlist(request).enqueue(new Callback<ApiResponse>() {
                @Override
                public void onResponse(Call<ApiResponse> call, Response<ApiResponse> response) {
                    if (response.isSuccessful() && response.body() != null && response.body().isSuccess()) {
                        isInWishlist = false;
                        updateWishlistUI();
                        Toast.makeText(ProductDetailActivity.this, "Đã xóa khỏi Wishlist.", Toast.LENGTH_SHORT).show();
                    }
                }
                @Override public void onFailure(Call<ApiResponse> call, Throwable t) { Log.e(TAG, "Remove Wishlist failed: ", t); }
            });
        } else {
            // Thêm vào wishlist (Sử dụng Request Body WishlistAddRequest)
            WishlistAddRequest request = new WishlistAddRequest(customerId, currentProductId);
            api.addToWishlist(request).enqueue(new Callback<ApiResponse>() {
                @Override
                public void onResponse(Call<ApiResponse> call, Response<ApiResponse> response) {
                    if (response.isSuccessful() && response.body() != null && response.body().isSuccess()) {
                        isInWishlist = true;
                        updateWishlistUI();
                        Toast.makeText(ProductDetailActivity.this, "Đã thêm vào Wishlist.", Toast.LENGTH_SHORT).show();
                    }
                }
                @Override public void onFailure(Call<ApiResponse> call, Throwable t) { Log.e(TAG, "Add Wishlist failed: ", t); }
            });
        }
    }

    // ⭐ HÀM CẬP NHẬT UI WISHLIST (Giữ nguyên)
    private void updateWishlistUI() {
        int color = ContextCompat.getColor(this, isInWishlist ? R.color.red : R.color.black);
        btnWishlist.setColorFilter(color);
    }

    private int getCurrentCustomerId() {
        SharedPreferences sp = getSharedPreferences("auth", MODE_PRIVATE);
        return sp.getInt("customerID", -1);
    }

    private void setupQuantityButtons() {
        tvQuantity.setText(String.valueOf(currentQuantity));

        btnIncreaseQuantity.setOnClickListener(v -> {
            int maxStock = (selectedVariant != null) ? selectedVariant.getStock() : Integer.MAX_VALUE;

            if (currentQuantity < maxStock) {
                currentQuantity++;
                tvQuantity.setText(String.valueOf(currentQuantity));
            } else if (selectedVariant != null) {
                Toast.makeText(this, "Đã đạt số lượng tồn kho tối đa (" + maxStock + ")", Toast.LENGTH_SHORT).show();
            }
        });

        btnDecreaseQuantity.setOnClickListener(v -> {
            if (currentQuantity > 1) {
                currentQuantity--;
                tvQuantity.setText(String.valueOf(currentQuantity));
            }
        });
    }

    private String normalizeImageUrl(String raw) {
        if (raw == null || raw.trim().isEmpty()) return null;
        if (raw.startsWith("http")) {
            return raw;
        }
        return BASE_IMAGE_URL + raw;
    }

    private void loadProductDetail(int productID) {
        api.getProductDetail(productID).enqueue(new Callback<ProductDetailResponse>() {
            @Override
            public void onResponse(Call<ProductDetailResponse> call, Response<ProductDetailResponse> response) {
                if (response.isSuccessful() && response.body() != null && response.body().isSuccess()) {
                    ProductDto product = response.body().getProduct();
                    if (product != null) {
                        displayProductDetails(product);
                        checkWishlistStatus(productID);
                    } else {
                        Log.e(TAG, "Load detail failed: Product data is null.");
                        Toast.makeText(ProductDetailActivity.this, "Không tìm thấy dữ liệu sản phẩm", Toast.LENGTH_SHORT).show();
                    }
                } else {
                    String msg = response.body() != null ? response.body().getMessage() : "Lỗi server không rõ";
                    Log.e(TAG, "Load detail failed. Code: " + response.code() + ", Message: " + msg);
                    Toast.makeText(ProductDetailActivity.this, "Không tải được chi tiết sản phẩm", Toast.LENGTH_SHORT).show();
                }
            }

            @Override
            public void onFailure(Call<ProductDetailResponse> call, Throwable t) {
                Log.e(TAG, "Load detail network error: ", t);
                Toast.makeText(ProductDetailActivity.this, "Lỗi kết nối", Toast.LENGTH_LONG).show();
            }
        });
    }

    // ⭐ HÀM KIỂM TRA WISHLIST (Giữ nguyên)
    private void checkWishlistStatus(int productID) {
        int customerId = getCurrentCustomerId();
        if (customerId == -1) return;

        api.checkWishlistStatus(customerId, productID).enqueue(new Callback<ApiResponse>() {
            @Override
            public void onResponse(Call<ApiResponse> call, Response<ApiResponse> response) {
                if (response.isSuccessful() && response.body() != null) {
                    isInWishlist = response.body().isSuccess();
                    updateWishlistUI();
                }
            }
            @Override public void onFailure(Call<ApiResponse> call, Throwable t) {}
        });
    }

    private void displayProductDetails(ProductDto p) {
        currentProductName = p.getProductName();
        txtName.setText(p.getProductName());

        // ⭐ BƯỚC 1: XỬ LÝ HIỂN THỊ GIÁ SALE DÙNG VARIANT MẶC ĐỊNH
        if (!p.getVariants().isEmpty()) {
            // Lấy biến thể có giá thấp nhất (thường là biến thể đầu tiên do ORDER BY v.price ASC)
            ProductDto.VariantDto defaultVariant = p.getVariants().get(0);

            // ⭐ GÁN BIẾN THỂ MẶC ĐỊNH CHO BIẾN TOÀN CỤC
            selectedVariantId = defaultVariant.getVariantID();
            selectedVariant = defaultVariant;

            // Áp dụng Logic Hiển thị Giá Sale
            if (defaultVariant.isDiscounted()) {
                // Có SALE: Hiển thị giá gốc bị gạch ngang
                tvOriginalPriceDetail.setText(formatCurrency(defaultVariant.getOriginalPrice()));
                tvOriginalPriceDetail.setPaintFlags(tvOriginalPriceDetail.getPaintFlags() | Paint.STRIKE_THRU_TEXT_FLAG);
                tvOriginalPriceDetail.setTextColor(ContextCompat.getColor(this, COLOR_ORIGINAL));
                tvOriginalPriceDetail.setVisibility(View.VISIBLE);

                // Hiển thị giá sale nổi bật
                txtPrice.setText(formatCurrency(defaultVariant.getSalePrice()));
                txtPrice.setTextColor(ContextCompat.getColor(this, COLOR_SALE));

            } else {
                // KHÔNG SALE: Chỉ hiển thị giá bình thường
                tvOriginalPriceDetail.setVisibility(View.GONE);
                txtPrice.setText(formatCurrency(defaultVariant.getSalePrice()));
                txtPrice.setTextColor(ContextCompat.getColor(this, COLOR_DEFAULT));
                txtPrice.setPaintFlags(0);
            }
        }
        // ⭐ KẾT THÚC XỬ LÝ GIÁ SALE

        txtDesc.setText(p.getDescription());

        setupImageViewer(p.getImages());
        setupVariants(p.getVariants());

        // HIỂN THỊ RATING DETAIL (Điểm số + RatingBar)
        float avgRating = p.getAverageRating();

        tvRatingScore.setText(String.format(Locale.US, "%.1f", avgRating));
        ratingBarProductDetail.setRating(avgRating);

        // HIỂN THỊ RATING SUMMARY (Cho phần review)
        int totalReviews = p.getTotalReviews();
        tvAverageRating.setText(String.format(Locale.US, "%.1f", avgRating));
        ratingBarSummary.setRating(avgRating);
        tvTotalReviews.setText(String.format("(%d Reviews)", totalReviews));
        btnViewAllReviews.setVisibility(totalReviews > 0 ? View.VISIBLE : View.GONE);
    }

    private void setupImageViewer(List<ProductDto.ImageDto> images) {
        if (images == null || images.isEmpty()) {
            txtImageCount.setText("0/0");
            return;
        }

        GalleryAdapter mainAdapter = new GalleryAdapter(this, images, this::showImagePreview);
        pagerImages.setAdapter(mainAdapter);

        recyclerGalleryThumbs.setLayoutManager(new LinearLayoutManager(this, LinearLayoutManager.HORIZONTAL, false));
        final GalleryAdapter[] thumbAdapter = new GalleryAdapter[1];
        thumbAdapter[0] = new GalleryAdapter(this, images, imageUrl -> {
            for (int i = 0; i < images.size(); i++) {
                if (normalizeImageUrl(images.get(i).getImageUrl()).equals(imageUrl)) {
                    pagerImages.setCurrentItem(i, true);
                    break;
                }
            }
        }, true);
        recyclerGalleryThumbs.setAdapter(thumbAdapter[0]);

        txtImageCount.setText(String.format("1/%d", images.size()));

        pagerImages.registerOnPageChangeCallback(new ViewPager2.OnPageChangeCallback() {
            @Override
            public void onPageSelected(int position) {
                super.onPageSelected(position);
                txtImageCount.setText(String.format("%d/%d", position + 1, images.size()));
                recyclerGalleryThumbs.smoothScrollToPosition(position);
                selectedThumb = position;
                if(thumbAdapter[0] != null) {
                    thumbAdapter[0].notifyDataSetChanged();
                }
            }
        });
    }

    private void setupVariants(List<ProductDto.VariantDto> variants) {
        layoutVariants.removeAllViews();
        btnAddToCart.setEnabled(true);

        if (variants == null || variants.isEmpty()) {
            btnAddToCart.setEnabled(false);
            btnAddToCart.setText("Sản phẩm chưa có phiên bản");
            btnIncreaseQuantity.setEnabled(false);
            btnDecreaseQuantity.setEnabled(false);
            return;
        }

        for (ProductDto.VariantDto v : variants) {
            MaterialButton btn = new MaterialButton(this);
            btn.setText(v.getAttributes());
            btn.setTextColor(Color.BLACK);
            btn.setBackgroundColor(Color.parseColor("#EEEEEE"));
            btn.setCornerRadius(12);
            btn.setStrokeWidth(2);
            btn.setStrokeColorResource(R.color.black);
            btn.setPadding(30, 15, 30, 15);
            btn.setTextSize(14);

            LinearLayout.LayoutParams params = new LinearLayout.LayoutParams(
                    LinearLayout.LayoutParams.WRAP_CONTENT,
                    LinearLayout.LayoutParams.WRAP_CONTENT
            );
            params.setMarginEnd(16);
            btn.setLayoutParams(params);


            if (v.getStock() <= 0) {
                btn.setEnabled(false);
                btn.setAlpha(0.5f);
                btn.setText(v.getAttributes() + " (Hết hàng)");
            }

            btn.setOnClickListener(x -> {
                // ⭐ SỬA: Cập nhật giá khi chọn variant
                updatePriceDisplay(v);

                selectedVariantId = v.getVariantID();
                selectedVariant = v;

                currentQuantity = 1;
                tvQuantity.setText(String.valueOf(currentQuantity));

                boolean hasStock = v.getStock() > 0;
                btnDecreaseQuantity.setEnabled(true);
                btnIncreaseQuantity.setEnabled(hasStock);
                btnAddToCart.setEnabled(hasStock);

                if (!hasStock) {
                    Toast.makeText(this, "Phiên bản này đã hết hàng.", Toast.LENGTH_SHORT).show();
                } else {
                    Toast.makeText(this, "Đã chọn: " + v.getAttributes(), Toast.LENGTH_SHORT).show();
                }


                for (int i = 0; i < layoutVariants.getChildCount(); i++) {
                    View child = layoutVariants.getChildAt(i);
                    if (child instanceof MaterialButton) {
                        MaterialButton other = (MaterialButton) child;
                        other.setBackgroundColor(Color.parseColor("#EEEEEE"));
                        other.setTextColor(Color.BLACK);
                    }
                }

                btn.setBackgroundColor(Color.parseColor("#FF6700"));
                btn.setTextColor(Color.WHITE);
            });
            layoutVariants.addView(btn);
        }

        btnIncreaseQuantity.setEnabled(false);
        btnDecreaseQuantity.setEnabled(false);
    }

    // ⭐ HÀM MỚI: CẬP NHẬT HIỂN THỊ GIÁ KHI CHỌN VARIANT MỚI
    private void updatePriceDisplay(ProductDto.VariantDto variant) {
        if (variant.isDiscounted()) {
            tvOriginalPriceDetail.setText(formatCurrency(variant.getOriginalPrice()));
            tvOriginalPriceDetail.setPaintFlags(tvOriginalPriceDetail.getPaintFlags() | Paint.STRIKE_THRU_TEXT_FLAG);
            tvOriginalPriceDetail.setTextColor(ContextCompat.getColor(this, COLOR_ORIGINAL));
            tvOriginalPriceDetail.setVisibility(View.VISIBLE);

            txtPrice.setText(formatCurrency(variant.getSalePrice()));
            txtPrice.setTextColor(ContextCompat.getColor(this, COLOR_SALE));
            txtPrice.setPaintFlags(0);
        } else {
            tvOriginalPriceDetail.setVisibility(View.GONE);
            txtPrice.setText(formatCurrency(variant.getSalePrice()));
            txtPrice.setTextColor(ContextCompat.getColor(this, COLOR_DEFAULT));
            txtPrice.setPaintFlags(0);
        }
    }


    // ⭐ XỬ LÝ CHUYỂN ACTIVITY SANG REVIEW LIST (Giữ nguyên)
    private void handleViewAllReviews() {
        Intent intent = new Intent(this, ReviewListActivity.class);
        intent.putExtra("PRODUCT_ID", currentProductId);
        intent.putExtra("PRODUCT_NAME", currentProductName);
        startActivity(intent);
    }


    private void handleAddToCart() {
        int customerId = getCurrentCustomerId();

        if (customerId == -1) {
            Toast.makeText(this, "Vui lòng đăng nhập để thêm sản phẩm", Toast.LENGTH_SHORT).show();
            startActivity(new Intent(this, LoginActivity.class));
            return;
        }

        if (selectedVariantId == -1) {
            Toast.makeText(this, "Vui lòng chọn một phiên bản sản phẩm", Toast.LENGTH_SHORT).show();
            return;
        }

        if (selectedVariant != null && selectedVariant.getStock() < currentQuantity) {
            Toast.makeText(this, "Số lượng trong kho không đủ (" + selectedVariant.getStock() + ")", Toast.LENGTH_SHORT).show();
            return;
        }

        btnAddToCart.setEnabled(false);

        api.addToCart(customerId, selectedVariantId, currentQuantity).enqueue(new Callback<ApiResponse>() {
            @Override
            public void onResponse(Call<ApiResponse> call, Response<ApiResponse> response) {
                btnAddToCart.setEnabled(true);
                Log.d(TAG, "Cart Add Response Code: " + response.code());

                if (response.code() >= 200 && response.code() < 300) {
                    if (response.body() != null) {
                        if (response.body().isSuccess()) {
                            Toast.makeText(ProductDetailActivity.this, "✅ Đã thêm " + currentQuantity + " sản phẩm vào giỏ hàng!", Toast.LENGTH_SHORT).show();
                            Log.i(TAG, "Add to Cart SUCCESS: " + response.body().getMessage());
                        } else {
                            String msg = response.body().getMessage();
                            Toast.makeText(ProductDetailActivity.this, "❌ Thêm thất bại: " + msg, Toast.LENGTH_LONG).show();
                            Log.e(TAG, "Add to Cart FAIL (Logic): " + msg);

                            if (selectedVariant != null && msg != null && (msg.toLowerCase().contains("hết hàng") || msg.toLowerCase().contains("kho không đủ"))) {
                                loadProductDetail(selectedVariant.getProductID());
                            }
                        }
                    } else {
                        Toast.makeText(ProductDetailActivity.this, "✅ Đã thêm vào giỏ hàng!", Toast.LENGTH_SHORT).show();
                        Log.w(TAG, "Add to Cart WARNING: Server successful code but empty body.");
                    }
                } else {
                    String errorMsg;
                    try {
                        errorMsg = response.errorBody() != null ? response.errorBody().string() : "Lỗi Server không rõ";
                    } catch (Exception e) {
                        errorMsg = "Lỗi đọc Body phản hồi.";
                    }
                    Log.e(TAG, "Add to Cart FAIL: HTTP Code " + response.code() + ", Detail: " + errorMsg);
                    Toast.makeText(ProductDetailActivity.this, "❌ Thêm thất bại (HTTP Code " + response.code() + ")", Toast.LENGTH_LONG).show();
                }
            }

            @Override
            public void onFailure(Call<ApiResponse> call, Throwable t) {
                btnAddToCart.setEnabled(true);
                Toast.makeText(ProductDetailActivity.this, "Lỗi kết nối mạng", Toast.LENGTH_SHORT).show();
                Log.e(TAG, "Network failure during addToCart: " + t.getMessage(), t);
            }
        });
    }

    private void showImagePreview(String imageUrl) {
        Dialog dialog = new Dialog(this, android.R.style.Theme_Black_NoTitleBar_Fullscreen);
        dialog.setContentView(R.layout.dialog_image_preview);
        ImageView imgPreview = dialog.findViewById(R.id.imgPreview);

        Glide.with(this)
                .load(normalizeImageUrl(imageUrl))
                .placeholder(R.drawable.ic_badminton_logo)
                .error(R.drawable.ic_badminton_logo)
                .transition(DrawableTransitionOptions.withCrossFade())
                .into(imgPreview);

        imgPreview.setOnClickListener(v -> dialog.dismiss());
        dialog.show();
    }
}