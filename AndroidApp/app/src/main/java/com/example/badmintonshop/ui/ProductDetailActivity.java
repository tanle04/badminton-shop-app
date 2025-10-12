package com.example.badmintonshop.ui;

import android.app.Dialog;
import android.graphics.Color;
import android.os.Bundle;
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

    private ApiService api;

    private static final String BASE_URL = "http://10.0.2.2/api/BadmintonShop/";
    private static final String BASE_IMAGE_URL = "http://10.0.2.2/api/BadmintonShop/images/uploads/";

    private int selectedThumb = -1;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_product_detail);

        pagerImages = findViewById(R.id.pagerProductImages);
        recyclerGalleryThumbs = findViewById(R.id.recyclerGalleryThumbs);
        txtName = findViewById(R.id.txtProductNameDetail);
        txtPrice = findViewById(R.id.txtProductPriceDetail);
        txtDesc = findViewById(R.id.txtProductDescriptionDetail);
        txtImageCount = findViewById(R.id.txtImageCount);
        layoutVariants = findViewById(R.id.layoutVariants);

        api = ApiClient.get(BASE_URL).create(ApiService.class);

        int productID = getIntent().getIntExtra("productID", -1);
        if (productID == -1) {
            Toast.makeText(this, "Không tìm thấy sản phẩm!", Toast.LENGTH_SHORT).show();
            finish();
            return;
        }

        loadProductDetail(productID);
    }

    private String normalizeImageUrl(String raw) {
        if (raw == null || raw.trim().isEmpty()) return null;
        raw = raw.trim();
        if (raw.startsWith("http")) {
            return raw.replace("/api/BadmintonShop/uploads/", "/api/BadmintonShop/images/uploads/");
        }
        raw = raw.replaceFirst("^/?(images/)?uploads/", "");
        return BASE_IMAGE_URL + raw;
    }

    private void loadProductDetail(int productID) {
        api.getProductDetail(productID).enqueue(new Callback<ProductDto>() {
            @Override
            public void onResponse(Call<ProductDto> call, Response<ProductDto> response) {
                if (!response.isSuccessful() || response.body() == null) {
                    Toast.makeText(ProductDetailActivity.this,
                            "Không tải được chi tiết sản phẩm",
                            Toast.LENGTH_SHORT).show();
                    return;
                }

                ProductDto p = response.body();

                // === Thông tin cơ bản ===
                txtName.setText(p.getProductName());
                txtPrice.setText(String.format("%,.0f ₫", p.getPrice()));
                txtDesc.setText(p.getDescription());

                // === Gallery ảnh ===
                List<ProductDto.ImageDto> images = p.getImages();
                if (images != null && !images.isEmpty()) {

                    // Adapter ảnh chính (ViewPager2)
                    GalleryAdapter mainAdapter = new GalleryAdapter(
                            ProductDetailActivity.this,
                            images,
                            ProductDetailActivity.this::showImagePreview
                    );
                    pagerImages.setAdapter(mainAdapter);

                    // Layout thumbnail
                    recyclerGalleryThumbs.setLayoutManager(
                            new LinearLayoutManager(ProductDetailActivity.this, LinearLayoutManager.HORIZONTAL, false)
                    );

                    // ⚠️ Khai báo trước biến thumbAdapter
                    final GalleryAdapter[] thumbAdapter = new GalleryAdapter[1];

                    // Gán adapter thực tế
                    thumbAdapter[0] = new GalleryAdapter(
                            ProductDetailActivity.this,
                            images,
                            imageUrl -> {
                                for (int i = 0; i < images.size(); i++) {
                                    if (normalizeImageUrl(images.get(i).getImageUrl()).equals(imageUrl)) {
                                        pagerImages.setCurrentItem(i, true);
                                        selectedThumb = i;
                                        recyclerGalleryThumbs.post(thumbAdapter[0]::notifyDataSetChanged);
                                        break;
                                    }
                                }
                            },
                            true // ✅ thumbnail mode
                    );

                    recyclerGalleryThumbs.setAdapter(thumbAdapter[0]);

                    // Hiển thị chỉ số ảnh đầu tiên
                    txtImageCount.setText(String.format("1/%d", images.size()));

                    pagerImages.registerOnPageChangeCallback(new ViewPager2.OnPageChangeCallback() {
                        @Override
                        public void onPageSelected(int position) {
                            super.onPageSelected(position);
                            txtImageCount.setText(String.format("%d/%d", position + 1, images.size()));
                            recyclerGalleryThumbs.smoothScrollToPosition(position);
                            selectedThumb = position;
                            thumbAdapter[0].notifyDataSetChanged();
                        }
                    });
                }


                // === Biến thể sản phẩm ===
                layoutVariants.removeAllViews();
                if (p.getVariants() != null && !p.getVariants().isEmpty()) {
                    for (ProductDto.VariantDto v : p.getVariants()) {
                        MaterialButton btn = new MaterialButton(ProductDetailActivity.this);
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
                        }

                        btn.setOnClickListener(x -> {
                            txtPrice.setText(String.format("%,.0f ₫", v.getPrice()));
                            Toast.makeText(ProductDetailActivity.this,
                                    "Đã chọn: " + v.getAttributes() +
                                            " - Giá " + String.format("%,.0f ₫", v.getPrice()),
                                    Toast.LENGTH_SHORT).show();

                            // Reset màu
                            for (int i = 0; i < layoutVariants.getChildCount(); i++) {
                                MaterialButton other = (MaterialButton) layoutVariants.getChildAt(i);
                                other.setBackgroundColor(Color.parseColor("#EEEEEE"));
                                other.setTextColor(Color.BLACK);
                            }

                            // Highlight
                            btn.setBackgroundColor(Color.parseColor("#FF6700"));
                            btn.setTextColor(Color.WHITE);
                        });

                        layoutVariants.addView(btn);
                    }
                }
            }

            @Override
            public void onFailure(Call<ProductDto> call, Throwable t) {
                Toast.makeText(ProductDetailActivity.this,
                        "Lỗi kết nối: " + t.getMessage(),
                        Toast.LENGTH_LONG).show();
            }
        });
    }

    // Hiển thị ảnh fullscreen
    private void showImagePreview(String imageUrl) {
        Dialog dialog = new Dialog(this, android.R.style.Theme_Black_NoTitleBar_Fullscreen);
        dialog.setContentView(R.layout.dialog_image_preview);
        ImageView imgPreview = dialog.findViewById(R.id.imgPreview);

        Glide.with(this)
                .load(imageUrl)
                .placeholder(R.drawable.ic_badminton_logo)
                .error(R.drawable.ic_badminton_logo)
                .transition(DrawableTransitionOptions.withCrossFade())
                .into(imgPreview);

        imgPreview.setOnClickListener(v -> dialog.dismiss());
        dialog.show();
    }
}
