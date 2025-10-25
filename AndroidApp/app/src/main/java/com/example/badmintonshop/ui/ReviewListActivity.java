package com.example.badmintonshop.ui;

import android.content.Intent;
import android.net.Uri; // Import Uri
import android.os.Bundle;
import android.util.Log;
import android.view.View;
import android.widget.ImageView;
import android.widget.RatingBar;
import android.widget.TextView;
import android.widget.Toast;

import androidx.appcompat.app.AppCompatActivity;
import androidx.recyclerview.widget.RecyclerView;

import com.example.badmintonshop.R;
import com.example.badmintonshop.adapter.DisplayReviewAdapter;
import com.example.badmintonshop.adapter.DisplayReviewAdapter.ReviewMediaClickListener;
import com.example.badmintonshop.network.ApiClient;
import com.example.badmintonshop.network.ApiService;
import com.example.badmintonshop.network.dto.ReviewListResponse;
import com.example.badmintonshop.network.dto.ReviewDto;
import com.google.android.material.chip.ChipGroup;

import java.util.List;
import java.util.Locale;

import retrofit2.Call;
import retrofit2.Callback;
import retrofit2.Response;

public class ReviewListActivity extends AppCompatActivity {

    private static final String TAG = "ReviewListActivity";
    private ApiService apiService;

    // UI Components
    private TextView tvProductNameHeader;
    private ImageView btnBack;
    private ChipGroup chipGroupFilters;
    private RecyclerView recyclerViewReviews;
    private TextView tvAverageRatingLarge;
    private TextView tvTotalReviewsCount;
    private RatingBar ratingBarReviewList;

    // State
    private int currentProductId;
    private String currentProductName;
    private int currentRatingFilter = 0;

    // ⭐ BASE URL CẦN THIẾT CHO VIỆC HIỂN THỊ MEDIA
    private static final String BASE_IMAGE_URL = "http://10.0.2.2/api/uploads/";

    // ⭐ Tạo Listener để truyền vào Adapter
    private final ReviewMediaClickListener mediaClickListener = new ReviewMediaClickListener() {
        @Override
        public void onMediaClick(String mediaUrl) {
            showMediaPreview(mediaUrl); // Gọi hàm xử lý preview/phát video
        }
    };

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_review_list);

        // 1. Nhận dữ liệu từ Intent
        Intent intent = getIntent();
        currentProductId = intent.getIntExtra("PRODUCT_ID", -1);
        currentProductName = intent.getStringExtra("PRODUCT_NAME");

        if (currentProductId == -1 || currentProductName == null) {
            Toast.makeText(this, "Không có thông tin sản phẩm để xem đánh giá.", Toast.LENGTH_SHORT).show();
            finish();
            return;
        }

        apiService = ApiClient.getApiService();

        // 2. Bind Views
        tvProductNameHeader = findViewById(R.id.tvProductNameHeader);
        btnBack = findViewById(R.id.btnBack);
        chipGroupFilters = findViewById(R.id.chipGroupFilters);
        recyclerViewReviews = findViewById(R.id.recyclerViewReviews);
        tvAverageRatingLarge = findViewById(R.id.tvAverageRatingLarge);
        tvTotalReviewsCount = findViewById(R.id.tvTotalReviewsCount);
        ratingBarReviewList = findViewById(R.id.ratingBarReviewList);

        // 3. Setup UI cơ bản
        tvProductNameHeader.setText(currentProductName);
        btnBack.setOnClickListener(v -> finish());
        recyclerViewReviews.setLayoutManager(new androidx.recyclerview.widget.LinearLayoutManager(this));

        // 4. Setup bộ lọc (Chips)
        setupFilters();

        // 5. Tải dữ liệu
        loadReviews(currentProductId, currentRatingFilter);
    }

    private void setupFilters() {
        chipGroupFilters.setOnCheckedStateChangeListener((group, checkedIds) -> {
            if (!checkedIds.isEmpty()) {
                int checkedId = checkedIds.get(0);
                currentRatingFilter = getRatingFromChipId(checkedId);
                loadReviews(currentProductId, currentRatingFilter);
            }
        });
    }

    /**
     * Hàm phụ trợ để map ID tài nguyên Chip với giá trị số sao thực tế (1-5).
     */
    private int getRatingFromChipId(int chipId) {
        if (chipId == R.id.chip_all) return 0;
        if (chipId == R.id.chip_with_media) return -1;
        if (chipId == R.id.chip_5_star) return 5;
        if (chipId == R.id.chip_4_star) return 4;
        if (chipId == R.id.chip_3_star) return 3;
        if (chipId == R.id.chip_2_star) return 2;
        if (chipId == R.id.chip_1_star) return 1;
        return 0;
    }


    private void loadReviews(int productId, int ratingFilter) {
        int apiRating = ratingFilter > 0 ? ratingFilter : 0;

        apiService.getReviewsByProduct(productId, apiRating).enqueue(new Callback<ReviewListResponse>() {
            @Override
            public void onResponse(Call<ReviewListResponse> call, Response<ReviewListResponse> response) {
                Log.d(TAG, "Reviews API Response Code: " + response.code());

                if (response.isSuccessful() && response.body() != null && response.body().isSuccess()) {

                    ReviewListResponse reviewResponse = response.body();
                    List<ReviewDto> reviews = reviewResponse.getItems();

                    if (reviews != null) {
                        Log.i(TAG, "Reviews loaded successfully. Total count: " + reviews.size());
                    }

                    // ⭐ Cập nhật tóm tắt UI
                    float avgRating = reviewResponse.getAverageRating();
                    int totalReviews = reviewResponse.getTotalReviews();

                    tvAverageRatingLarge.setText(String.format(Locale.US, "%.1f", avgRating));
                    ratingBarReviewList.setRating(avgRating);
                    tvTotalReviewsCount.setText(String.format(Locale.US, "(%d đánh giá)", totalReviews));

                    // ⭐ Gán Adapter để hiển thị danh sách
                    if (reviews != null && !reviews.isEmpty()) {
                        // SỬ DỤNG DisplayReviewAdapter và truyền Listener đã tạo
                        DisplayReviewAdapter adapter = new DisplayReviewAdapter(ReviewListActivity.this, reviews, mediaClickListener);
                        recyclerViewReviews.setAdapter(adapter);
                        Log.d(TAG, "Adapter attached with " + reviews.size() + " items.");
                        recyclerViewReviews.setVisibility(View.VISIBLE);
                    } else {
                        recyclerViewReviews.setVisibility(View.GONE);
                        Toast.makeText(ReviewListActivity.this, "Không tìm thấy đánh giá nào.", Toast.LENGTH_SHORT).show();
                        Log.w(TAG, "No reviews found for productID: " + productId);
                    }

                } else {
                    String msg = response.body() != null ? response.body().getMessage() : "Lỗi tải đánh giá từ server";
                    Log.e(TAG, "Reviews API Failed. Code: " + response.code() + ", Message: " + msg);
                    Toast.makeText(ReviewListActivity.this, "Lỗi: " + msg, Toast.LENGTH_LONG).show();
                }
            }

            @Override
            public void onFailure(Call<ReviewListResponse> call, Throwable t) {
                Log.e(TAG, "Network error loading reviews: " + t.getMessage(), t);
                Toast.makeText(ReviewListActivity.this, "Lỗi kết nối mạng khi tải đánh giá", Toast.LENGTH_LONG).show();
            }
        });
    }

    // ⭐ HÀM MỚI: XỬ LÝ SỰ KIỆN CLICK MEDIA
    private void showMediaPreview(String mediaUrl) {
        if (mediaUrl == null || mediaUrl.isEmpty()) return;

        // ⭐ 1. TẠO FULL URL
        String fullUrl = BASE_IMAGE_URL + mediaUrl;

        // ⭐ 2. XÁC ĐỊNH LOẠI MEDIA
        String urlLower = mediaUrl.toLowerCase(Locale.US);
        boolean isVideo = urlLower.endsWith(".mp4") || urlLower.endsWith(".mov") || urlLower.endsWith(".webm") || urlLower.endsWith(".avi");

        if (isVideo) {
            // PHÁT VIDEO
            try {
                Uri videoUri = Uri.parse(fullUrl);
                Intent intent = new Intent(Intent.ACTION_VIEW, videoUri);
                intent.setDataAndType(videoUri, "video/*");
                startActivity(intent);
            } catch (Exception e) {
                Toast.makeText(this, "Không tìm thấy ứng dụng phát video.", Toast.LENGTH_SHORT).show();
                Log.e(TAG, "Error playing video: " + e.getMessage());
            }

        } else {
            // HIỂN THỊ ẢNH PREVIEW

            // ⭐ LƯU Ý: Bạn cần tạo FullScreenImageActivity để hiển thị ảnh lớn
            // Sử dụng Dialog hoặc Activity riêng để hiển thị ảnh full screen
            Intent intent = new Intent(this, FullScreenImageActivity.class);
            intent.putExtra("IMAGE_URL", fullUrl);
            startActivity(intent);

            // Nếu bạn không có FullScreenImageActivity, bạn có thể dùng Dialog đơn giản:
            // showSimpleImageDialog(fullUrl);
        }
    }

    // Tùy chọn: Hàm đơn giản để hiển thị Dialog ảnh (Chỉ để gỡ lỗi nhanh)
    /*
    private void showSimpleImageDialog(String imageUrl) {
        Dialog dialog = new Dialog(this, android.R.style.Theme_Black_NoTitleBar_Fullscreen);
        dialog.setContentView(R.layout.dialog_image_preview_simple); // Cần tạo layout này
        ImageView img = dialog.findViewById(R.id.img_full_screen);
        Glide.with(this).load(imageUrl).into(img);
        img.setOnClickListener(v -> dialog.dismiss());
        dialog.show();
    }
    */
}