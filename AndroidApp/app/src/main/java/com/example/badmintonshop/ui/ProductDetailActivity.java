package com.example.badmintonshop.ui;

import android.app.Dialog;
import android.content.Intent;
import android.content.SharedPreferences;
import android.graphics.Color;
import android.os.Bundle;
import android.widget.ImageButton;
import android.widget.ImageView;
import android.widget.LinearLayout;
import android.widget.TextView;
import android.widget.Toast;

import androidx.appcompat.app.AppCompatActivity;
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
import com.example.badmintonshop.network.dto.ProductDto;
import com.google.android.material.button.MaterialButton;

import java.util.List;

import retrofit2.Call;
import retrofit2.Callback;
import retrofit2.Response;

public class ProductDetailActivity extends AppCompatActivity {

    private ViewPager2 pagerImages;
    private RecyclerView recyclerGalleryThumbs;
    private TextView txtName, txtPrice, txtDesc, txtImageCount;
    private LinearLayout layoutVariants;
    private MaterialButton btnAddToCart;

    // 🚩 NEW: Views for the quantity selector
    private ImageButton btnDecreaseQuantity, btnIncreaseQuantity;
    private TextView tvQuantity;

    private ApiService api;
    private int selectedVariantId = -1;
    private int currentQuantity = 1; // 🚩 NEW: Variable to store the current quantity

    private static final String BASE_IMAGE_URL = "http://10.0.2.2/api/BadmintonShop/images/uploads/";
    private int selectedThumb = -1;

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

        // 🚩 NEW: Bind quantity views
        btnDecreaseQuantity = findViewById(R.id.btnDecreaseQuantity);
        tvQuantity = findViewById(R.id.tvQuantity);
        btnIncreaseQuantity = findViewById(R.id.btnIncreaseQuantity);

        api = ApiClient.getApiService();

        int productID = getIntent().getIntExtra("productID", -1);
        if (productID == -1) {
            Toast.makeText(this, "Không tìm thấy sản phẩm!", Toast.LENGTH_SHORT).show();
            finish();
            return;
        }

        loadProductDetail(productID);
        setupQuantityButtons(); // 🚩 NEW: Set up listeners for quantity buttons
        btnAddToCart.setOnClickListener(v -> handleAddToCart());
    }

    // 🚩 NEW: Method to set up listeners for the quantity buttons
    private void setupQuantityButtons() {
        tvQuantity.setText(String.valueOf(currentQuantity));

        btnIncreaseQuantity.setOnClickListener(v -> {
            // You can add logic here to check against stock if available
            currentQuantity++;
            tvQuantity.setText(String.valueOf(currentQuantity));
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
        api.getProductDetail(productID).enqueue(new Callback<ProductDto>() {
            @Override
            public void onResponse(Call<ProductDto> call, Response<ProductDto> response) {
                if (response.isSuccessful() && response.body() != null) {
                    displayProductDetails(response.body());
                } else {
                    Toast.makeText(ProductDetailActivity.this, "Không tải được chi tiết sản phẩm", Toast.LENGTH_SHORT).show();
                }
            }

            @Override
            public void onFailure(Call<ProductDto> call, Throwable t) {
                Toast.makeText(ProductDetailActivity.this, "Lỗi kết nối: " + t.getMessage(), Toast.LENGTH_LONG).show();
            }
        });
    }

    private void displayProductDetails(ProductDto p) {
        txtName.setText(p.getProductName());
        txtPrice.setText(String.format("%,.0f ₫", p.getPrice()));
        txtDesc.setText(p.getDescription());
        setupImageViewer(p.getImages());
        setupVariants(p.getVariants());
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
        if (variants == null || variants.isEmpty()) {
            btnAddToCart.setEnabled(false);
            btnAddToCart.setText("Sản phẩm chưa có phiên bản");
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

            if (v.getStock() <= 0) {
                btn.setEnabled(false);
                btn.setAlpha(0.5f);
                btn.setText(v.getAttributes() + " (Hết hàng)");
            }

            btn.setOnClickListener(x -> {
                txtPrice.setText(String.format("%,.0f ₫", v.getPrice()));
                selectedVariantId = v.getVariantID();
                Toast.makeText(this, "Đã chọn: " + v.getAttributes(), Toast.LENGTH_SHORT).show();

                for (int i = 0; i < layoutVariants.getChildCount(); i++) {
                    MaterialButton other = (MaterialButton) layoutVariants.getChildAt(i);
                    other.setBackgroundColor(Color.parseColor("#EEEEEE"));
                    other.setTextColor(Color.BLACK);
                }

                btn.setBackgroundColor(Color.parseColor("#FF6700"));
                btn.setTextColor(Color.WHITE);
            });
            layoutVariants.addView(btn);
        }
    }

    private void handleAddToCart() {
        SharedPreferences prefs = getSharedPreferences("auth", MODE_PRIVATE);
        int customerId = prefs.getInt("customerID", -1);

        if (customerId == -1) {
            Toast.makeText(this, "Vui lòng đăng nhập để thêm sản phẩm", Toast.LENGTH_SHORT).show();
            startActivity(new Intent(this, LoginActivity.class));
            return;
        }

        if (selectedVariantId == -1) {
            Toast.makeText(this, "Vui lòng chọn một phiên bản sản phẩm", Toast.LENGTH_SHORT).show();
            return;
        }

        // 🚩 CORRECTED: Pass the currentQuantity variable to the API call
        api.addToCart(customerId, selectedVariantId, currentQuantity).enqueue(new Callback<ApiResponse>() {
            @Override
            public void onResponse(Call<ApiResponse> call, Response<ApiResponse> response) {
                if (response.isSuccessful() && response.body() != null && response.body().isSuccess()) {
                    Toast.makeText(ProductDetailActivity.this, "Đã thêm vào giỏ hàng!", Toast.LENGTH_SHORT).show();
                } else {
                    Toast.makeText(ProductDetailActivity.this, "Thêm thất bại, vui lòng thử lại", Toast.LENGTH_SHORT).show();
                }
            }

            @Override
            public void onFailure(Call<ApiResponse> call, Throwable t) {
                Toast.makeText(ProductDetailActivity.this, "Lỗi kết nối: " + t.getMessage(), Toast.LENGTH_SHORT).show();
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