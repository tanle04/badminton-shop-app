package com.example.badmintonshop.ui;

import android.content.Intent;
import android.net.Uri;
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
import com.example.badmintonshop.network.ApiClient; // ⭐ BƯỚC 1: Import ApiClient
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

    // ⭐ BƯỚC 2: Xóa BASE_IMAGE_URL bị sai
    // private static final String BASE_IMAGE_URL = "http://10.0.2.2/api/uploads/"; // ❌ XÓA DÒNG NÀY

    // ⭐ Tạo Listener để truyền vào Adapter
    private final ReviewMediaClickListener mediaClickListener = new ReviewMediaClickListener() {
        @Override
        public void onMediaClick(String mediaUrl) {
            // mediaUrl ở đây là đường dẫn tương đối, ví dụ: "reviews/image.jpg"
            showMediaPreview(mediaUrl);
        }
    };

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_review_list);

        // ... (Code onCreate, setupFilters, getRatingFromChipId giữ nguyên) ...

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
                // ... (Code onResponse giữ nguyên) ...
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
                // ... (Code onFailure giữ nguyên) ...
                Log.e(TAG, "Network error loading reviews: " + t.getMessage(), t);
                Toast.makeText(ReviewListActivity.this, "Lỗi kết nối mạng khi tải đánh giá", Toast.LENGTH_LONG).show();
            }
        });
    }

    // ⭐ BƯỚC 3: THÊM HÀM NORMALIZE NÀY (Giống hệt hàm của GalleryAdapter)
    /**
     * Chuẩn hóa đường dẫn tương đối (ví dụ: "reviews/img.jpg")
     * thành một URL đầy đủ (ví dụ: "https://domain.com/storage/reviews/img.jpg")
     */
    private String normalizeMediaUrl(String raw) {
        if (raw == null || raw.trim().isEmpty()) {
            return null;
        }
        raw = raw.trim();

        // Nếu API đã trả về URL đầy đủ
        if (raw.startsWith("http")) {
            return raw;
        }

        // Nếu API trả về đường dẫn tương đối
        if (raw.startsWith("/")) {
            raw = raw.substring(1);
        }

        // Nối với hằng số BASE_STORAGE_URL từ ApiClient
        return ApiClient.BASE_STORAGE_URL + raw;
    }


    // ⭐ BƯỚC 4: SỬA LẠI HÀM SHOWMEDIAPREVIEW
    private void showMediaPreview(String mediaUrl) { // mediaUrl là đường dẫn tương đối
        if (mediaUrl == null || mediaUrl.isEmpty()) return;

        // 1. TẠO FULL URL bằng hàm normalize mới
        String fullUrl = normalizeMediaUrl(mediaUrl);

        if (fullUrl == null) {
            Toast.makeText(this, "Đường dẫn media không hợp lệ.", Toast.LENGTH_SHORT).show();
            return;
        }
        Log.d(TAG, "Opening media: " + fullUrl);

        // 2. XÁC ĐỊNH LOẠI MEDIA
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
            Intent intent = new Intent(this, FullScreenImageActivity.class);
            intent.putExtra("IMAGE_URL", fullUrl); // Gửi URL đầy đủ
            startActivity(intent);
        }
    }
}