package com.example.badmintonshop.ui;

import android.app.Dialog;
import android.content.Intent;
import android.content.SharedPreferences;
import android.graphics.Color;
import android.os.Bundle;
import android.view.View;
import android.widget.ImageButton;
import android.widget.ImageView;
import android.widget.LinearLayout;
import android.widget.TextView;
import android.widget.Toast;
import android.util.Log;

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
import com.example.badmintonshop.network.dto.ProductDetailResponse;
import com.example.badmintonshop.network.dto.ProductDto;
import com.google.android.material.button.MaterialButton;

import java.util.List;
import java.util.Locale;

import retrofit2.Call;
import retrofit2.Callback;
import retrofit2.Response;

public class ProductDetailActivity extends AppCompatActivity {

    private ViewPager2 pagerImages;
    private RecyclerView recyclerGalleryThumbs;
    private TextView txtName, txtPrice, txtDesc, txtImageCount;
    private LinearLayout layoutVariants;
    private MaterialButton btnAddToCart;

    private ImageButton btnDecreaseQuantity, btnIncreaseQuantity;
    private TextView tvQuantity;

    private ApiService api;
    private int selectedVariantId = -1;
    private int currentQuantity = 1;

    // ⭐ NEW: Lưu trữ đối tượng biến thể đã chọn
    private ProductDto.VariantDto selectedVariant = null;

    private static final String BASE_IMAGE_URL = "http://10.0.2.2/api/BadmintonShop/images/uploads/";
    private int selectedThumb = -1;
    private static final String TAG = "PRODUCT_DETAIL_DEBUG";

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

        api = ApiClient.getApiService();

        int productID = getIntent().getIntExtra("productID", -1);
        if (productID == -1) {
            Toast.makeText(this, "Không tìm thấy sản phẩm!", Toast.LENGTH_SHORT).show();
            finish();
            return;
        }

        loadProductDetail(productID);
        setupQuantityButtons();
        btnAddToCart.setOnClickListener(v -> handleAddToCart());


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
        // ⭐ SỬA: Dùng ProductDetailResponse
        api.getProductDetail(productID).enqueue(new Callback<ProductDetailResponse>() {
            @Override
            public void onResponse(Call<ProductDetailResponse> call, Response<ProductDetailResponse> response) {
                // Kiểm tra HTTP success và logic success
                if (response.isSuccessful() && response.body() != null && response.body().isSuccess()) {
                    // ⭐ SỬA LOGIC: Lấy ProductDto từ response.body().getProduct()
                    ProductDto product = response.body().getProduct();
                    if (product != null) {
                        displayProductDetails(product);
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
            // ⭐ SỬA KIỂU DỮ LIỆU: Phải là ProductDetailResponse
            public void onFailure(Call<ProductDetailResponse> call, Throwable t) {
                Log.e(TAG, "Load detail network error: ", t);
                Toast.makeText(ProductDetailActivity.this, "Lỗi kết nối", Toast.LENGTH_LONG).show();
            }
        });
    }

    private void displayProductDetails(ProductDto p) {
        txtName.setText(p.getProductName());
        // Hiển thị giá mặc định (có thể là giá thấp nhất hoặc giá duy nhất)
        txtPrice.setText(String.format(Locale.GERMAN, "%,.0f ₫", p.getPrice()));
        txtDesc.setText(p.getDescription());
        setupImageViewer(p.getImages());
        setupVariants(p.getVariants());
    }

    private void setupImageViewer(List<ProductDto.ImageDto> images) {
        // ⭐ Cải tiến: Sử dụng List getters an toàn (đã được sửa trong DTO)
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
            // Vô hiệu hóa bộ chọn số lượng nếu không có biến thể
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
                txtPrice.setText(String.format(Locale.GERMAN, "%,.0f ₫", v.getPrice()));
                selectedVariantId = v.getVariantID();
                selectedVariant = v;

                currentQuantity = 1;
                tvQuantity.setText(String.valueOf(currentQuantity));

                // Kích hoạt/Vô hiệu hóa nút tăng/giảm dựa trên tồn kho
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

        // Đảm bảo nút tăng/giảm số lượng bị vô hiệu hóa nếu chưa chọn biến thể
        btnIncreaseQuantity.setEnabled(false);
        btnDecreaseQuantity.setEnabled(false);
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

        if (selectedVariant != null && selectedVariant.getStock() < currentQuantity) {
            Toast.makeText(this, "Số lượng trong kho không đủ (" + selectedVariant.getStock() + ")", Toast.LENGTH_SHORT).show();
            return;
        }

        btnAddToCart.setEnabled(false); // Ngăn chặn click đúp

        // Gọi API với số lượng hiện tại
        api.addToCart(customerId, selectedVariantId, currentQuantity).enqueue(new Callback<ApiResponse>() {
            @Override
            public void onResponse(Call<ApiResponse> call, Response<ApiResponse> response) {
                btnAddToCart.setEnabled(true); // Bật lại nút
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

                            // Nếu lỗi do hết hàng, cần tải lại chi tiết sản phẩm để cập nhật UI
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
                btnAddToCart.setEnabled(true); // Bật lại nút
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