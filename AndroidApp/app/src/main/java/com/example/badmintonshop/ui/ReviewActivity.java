package com.example.badmintonshop.ui;

import android.content.SharedPreferences;
import android.net.Uri;
import android.os.Bundle;
import android.util.Log;
import android.view.MenuItem;
import android.widget.Toast;
import android.database.Cursor;
import android.provider.MediaStore;
import android.content.Intent;
import android.content.pm.PackageManager;

import androidx.annotation.NonNull;
import androidx.appcompat.app.AppCompatActivity;
import androidx.core.app.ActivityCompat;
import androidx.core.content.ContextCompat;
import androidx.recyclerview.widget.LinearLayoutManager;
import androidx.recyclerview.widget.RecyclerView;

import com.example.badmintonshop.R;
import com.example.badmintonshop.adapter.ReviewAdapter;
import com.example.badmintonshop.model.ReviewItemModel;
import com.example.badmintonshop.network.ApiClient;
import com.example.badmintonshop.network.ApiService;
import com.example.badmintonshop.network.dto.ApiResponse;
import com.example.badmintonshop.network.dto.OrderDetailDto;
import com.example.badmintonshop.network.dto.OrderDetailsListResponse;
import com.example.badmintonshop.network.dto.ReviewSubmitRequest;
import com.google.android.material.button.MaterialButton;
import com.google.android.material.appbar.MaterialToolbar;
import com.google.gson.Gson;

import java.io.File;
import java.util.ArrayList;
import java.util.List;
import java.util.Locale;

import okhttp3.MediaType;
import okhttp3.MultipartBody;
import okhttp3.RequestBody;
import retrofit2.Call;
import retrofit2.Callback;
import retrofit2.Response;

public class ReviewActivity extends AppCompatActivity implements ReviewAdapter.ReviewAdapterListener {

    private static final String TAG = "ReviewActivityDebug";
    private static final int MAX_PHOTOS = 5;
    private static final int MAX_VIDEOS = 1;

    private static final int REQUEST_CODE_PICK_PHOTO = 1001;
    private static final int REQUEST_CODE_PICK_VIDEO = 1002;
    private static final int PERMISSION_REQUEST_CODE = 1003;

    private int currentReviewItemPosition = -1;
    private boolean isAwaitingPhotoPermission = false;

    private RecyclerView recyclerView;
    private MaterialButton btnSubmitAllReviews;
    private ReviewAdapter reviewAdapter;
    private int orderId;
    private ApiService api;
    private final Gson gson = new Gson();

    private final List<ReviewItemModel> reviewItemsList = new ArrayList<>();

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_review);

        api = ApiClient.getApiService();
        orderId = getIntent().getIntExtra("orderID", -1);

        if (orderId == -1) {
            Toast.makeText(this, "Không tìm thấy ID đơn hàng.", Toast.LENGTH_SHORT).show();
            finish();
            return;
        }

        MaterialToolbar toolbar = findViewById(R.id.toolbar);
        setSupportActionBar(toolbar);
        if (getSupportActionBar() != null) {
            getSupportActionBar().setTitle("Leave all reviews");
            getSupportActionBar().setDisplayHomeAsUpEnabled(true);
        }

        recyclerView = findViewById(R.id.recycler_review_items);
        btnSubmitAllReviews = findViewById(R.id.btn_submit_all_reviews);
        btnSubmitAllReviews.setEnabled(false);

        setupRecyclerView();
        loadOrderDetailsForReview(orderId);

        btnSubmitAllReviews.setOnClickListener(v -> submitReviews());
    }

    @Override
    public boolean onOptionsItemSelected(@NonNull MenuItem item) {
        if (item.getItemId() == android.R.id.home) {
            finish();
            return true;
        }
        return super.onOptionsItemSelected(item);
    }

    // ⭐ HÀM KIỂM TRA VÀ YÊU CẦU QUYỀN
    private void requestStoragePermission(int position, boolean isPhoto) {
        if (ContextCompat.checkSelfPermission(this, android.Manifest.permission.READ_EXTERNAL_STORAGE)
                != PackageManager.PERMISSION_GRANTED) {

            currentReviewItemPosition = position;
            isAwaitingPhotoPermission = isPhoto;

            ActivityCompat.requestPermissions(this,
                    new String[]{android.Manifest.permission.READ_EXTERNAL_STORAGE},
                    PERMISSION_REQUEST_CODE);
        } else {
            if (isPhoto) {
                onPhotoIntent(position);
            } else {
                onVideoIntent(position);
            }
        }
    }

    // ⭐ XỬ LÝ KẾT QUẢ YÊU CẦU QUYỀN
    @Override
    public void onRequestPermissionsResult(int requestCode, @NonNull String[] permissions, @NonNull int[] grantResults) {
        super.onRequestPermissionsResult(requestCode, permissions, grantResults);

        if (requestCode == PERMISSION_REQUEST_CODE) {
            if (grantResults.length > 0 && grantResults[0] == PackageManager.PERMISSION_GRANTED) {
                if (currentReviewItemPosition != -1) {
                    if (isAwaitingPhotoPermission) {
                        onPhotoIntent(currentReviewItemPosition);
                    } else {
                        onVideoIntent(currentReviewItemPosition);
                    }
                }
            } else {
                Toast.makeText(this, "Không thể gửi ảnh/video do thiếu quyền truy cập bộ nhớ.", Toast.LENGTH_LONG).show();
            }
        }
    }


    // ⭐ 1. PHƯƠNG THỨC XỬ LÝ CLICK (Gọi hàm kiểm tra quyền)
    @Override
    public void onPhotoClicked(int position) {
        requestStoragePermission(position, true);
    }

    @Override
    public void onVideoClicked(int position) {
        requestStoragePermission(position, false);
    }

    // ⭐ HÀM CHỌN ẢNH (Chứa logic Intent)
    private void onPhotoIntent(int position) {
        currentReviewItemPosition = position;
        Intent intent = new Intent(Intent.ACTION_PICK);
        intent.setType("image/*");
        intent.putExtra(Intent.EXTRA_ALLOW_MULTIPLE, true);
        startActivityForResult(Intent.createChooser(intent, "Chọn ảnh sản phẩm"), REQUEST_CODE_PICK_PHOTO);
    }

    // ⭐ HÀM CHỌN VIDEO (Chứa logic Intent)
    private void onVideoIntent(int position) {
        currentReviewItemPosition = position;
        Intent intent = new Intent(Intent.ACTION_PICK);
        intent.setType("video/*");
        startActivityForResult(Intent.createChooser(intent, "Chọn video sản phẩm"), REQUEST_CODE_PICK_VIDEO);
    }

    // Logic onMediaDeleted (Đã sửa)
    @Override
    public void onMediaDeleted(int reviewPosition, int mediaPosition) {
        if (reviewPosition >= 0 && reviewPosition < reviewItemsList.size()) {
            ReviewItemModel model = reviewItemsList.get(reviewPosition);

            List<Uri> allMedia = new ArrayList<>();
            allMedia.addAll(model.getPhotoUris());
            allMedia.addAll(model.getVideoUris());

            if (mediaPosition >= 0 && mediaPosition < allMedia.size()) {
                Uri deletedUri = allMedia.get(mediaPosition);

                if (model.getVideoUris().contains(deletedUri)) {
                    model.getVideoUris().remove(deletedUri);
                    Toast.makeText(this, "Đã xóa video.", Toast.LENGTH_SHORT).show();
                } else if (model.getPhotoUris().contains(deletedUri)) {
                    model.getPhotoUris().remove(deletedUri);
                    Toast.makeText(this, "Đã xóa ảnh.", Toast.LENGTH_SHORT).show();
                }
            }
            reviewAdapter.notifyItemChanged(reviewPosition);
        }
    }


    // Logic onActivityResult (Đã sửa)
    @Override
    protected void onActivityResult(int requestCode, int resultCode, Intent data) {
        super.onActivityResult(requestCode, resultCode, data);

        if (resultCode == RESULT_OK && data != null && currentReviewItemPosition != -1) {
            ReviewItemModel currentModel = reviewItemsList.get(currentReviewItemPosition);

            if (requestCode == REQUEST_CODE_PICK_PHOTO) {
                List<Uri> newUris = new ArrayList<>();
                // Lấy nhiều ảnh
                if (data.getClipData() != null) {
                    int count = data.getClipData().getItemCount();
                    for (int i = 0; i < count; i++) {
                        newUris.add(data.getClipData().getItemAt(i).getUri());
                    }
                } else if (data.getData() != null) {
                    newUris.add(data.getData());
                }

                if (!newUris.isEmpty()) {
                    List<Uri> currentPhotos = currentModel.getPhotoUris();

                    // Thêm ảnh mới vào danh sách hiện tại
                    currentPhotos.addAll(newUris);

                    if (currentPhotos.size() > MAX_PHOTOS) {
                        currentPhotos = currentPhotos.subList(0, MAX_PHOTOS);
                        Toast.makeText(this, "Chỉ được chọn tối đa " + MAX_PHOTOS + " ảnh. Các ảnh sau bị bỏ qua.", Toast.LENGTH_LONG).show();
                    }

                    currentModel.setPhotoUris(currentPhotos);
                    Toast.makeText(this, "Đã thêm ảnh. Tổng cộng: " + currentPhotos.size() + " ảnh.", Toast.LENGTH_SHORT).show();
                }

            } else if (requestCode == REQUEST_CODE_PICK_VIDEO) {
                // Lấy video
                Uri videoUri = data.getData();
                if (videoUri != null) {
                    List<Uri> currentVideos = currentModel.getVideoUris();

                    // Chỉ thêm 1 video mới
                    if (currentVideos.size() >= MAX_VIDEOS) {
                        currentVideos.clear(); // Xóa video cũ
                        Toast.makeText(this, "Đã thay thế video cũ.", Toast.LENGTH_SHORT).show();
                    }

                    currentVideos.add(videoUri);

                    currentModel.setVideoUris(currentVideos);
                    Toast.makeText(this, "Đã thêm 1 video. Tổng cộng: " + currentVideos.size() + " video.", Toast.LENGTH_SHORT).show();
                }
            }
            reviewAdapter.notifyItemChanged(currentReviewItemPosition);
        }
        currentReviewItemPosition = -1;
    }

    // -----------------------------------------------------------------
    // CÁC HÀM CƠ BẢN
    // -----------------------------------------------------------------

    private int getCurrentCustomerId() {
        SharedPreferences sp = getSharedPreferences("auth", MODE_PRIVATE);
        return sp.getInt("customerID", -1);
    }

    private void setupRecyclerView() {
        recyclerView.setLayoutManager(new LinearLayoutManager(this));
        reviewAdapter = new ReviewAdapter(this, reviewItemsList, this);
        recyclerView.setAdapter(reviewAdapter);
    }

    private List<ReviewItemModel> convertOrderDetailsToReviewItems(List<OrderDetailDto> orderDetails) {
        List<ReviewItemModel> items = new ArrayList<>();
        if (orderDetails != null) {
            for (OrderDetailDto detail : orderDetails) {
                if (!detail.isReviewed()) {
                    items.add(new ReviewItemModel(detail));
                }
            }
        }
        return items;
    }

    private void loadOrderDetailsForReview(int orderId) {
        Log.d(TAG, "Loading details for OrderID: " + orderId);
        btnSubmitAllReviews.setEnabled(false);

        api.getOrderDetails(orderId).enqueue(new Callback<OrderDetailsListResponse>() {
            @Override
            public void onResponse(Call<OrderDetailsListResponse> call, Response<OrderDetailsListResponse> response) {

                if (response.isSuccessful() && response.body() != null && response.body().isSuccess()) {
                    List<OrderDetailDto> orderDetails = response.body().getOrderDetails();

                    List<ReviewItemModel> newReviews = convertOrderDetailsToReviewItems(orderDetails);

                    if (!newReviews.isEmpty()) {
                        reviewItemsList.clear();
                        reviewItemsList.addAll(newReviews);
                        reviewAdapter.updateData(reviewItemsList);
                        btnSubmitAllReviews.setEnabled(true);
                        Toast.makeText(ReviewActivity.this, "Sẵn sàng để đánh giá " + newReviews.size() + " sản phẩm.", Toast.LENGTH_SHORT).show();
                    } else {
                        Toast.makeText(ReviewActivity.this, "Không có sản phẩm nào cần đánh giá trong đơn hàng này.", Toast.LENGTH_SHORT).show();
                        finish();
                    }
                } else {
                    String msg = response.body() != null ? response.body().getMessage() : "HTTP " + response.code();
                    Toast.makeText(ReviewActivity.this, "Lỗi tải chi tiết đơn hàng: " + msg, Toast.LENGTH_LONG).show();
                    Log.e(TAG, "API Load Details Failed: " + msg);
                    finish();
                }
            }

            @Override
            public void onFailure(Call<OrderDetailsListResponse> call, Throwable t) {
                btnSubmitAllReviews.setEnabled(true);
                Toast.makeText(ReviewActivity.this, "Lỗi kết nối mạng.", Toast.LENGTH_SHORT).show();
                Log.e(TAG, "Network Error loading order details: " + t.getMessage());
                finish();
            }
        });
    }

    private String getRealPathFromURI(Uri contentUri) {
        String[] proj = { MediaStore.Images.Media.DATA };
        Cursor cursor = getContentResolver().query(contentUri, proj, null, null, null);
        if (cursor == null) return contentUri.getPath();

        try {
            if (cursor.moveToFirst()) {
                int column_index = cursor.getColumnIndexOrThrow(MediaStore.Images.Media.DATA);
                return cursor.getString(column_index);
            }
        } finally {
            if (cursor != null) cursor.close();
        }
        return null;
    }

    private MultipartBody.Part prepareFilePart(String partName, Uri fileUri) {
        String filePath = getRealPathFromURI(fileUri);
        if (filePath == null) return null;

        File file = new File(filePath);
        if (!file.exists()) return null;

        try {
            String mimeType = getContentResolver().getType(fileUri);
            if (mimeType == null) mimeType = "application/octet-stream";

            RequestBody requestFile = RequestBody.create(MediaType.parse(mimeType), file);

            return MultipartBody.Part.createFormData(partName, file.getName(), requestFile);
        } catch (Exception e) {
            Log.e(TAG, "Lỗi chuẩn bị tệp: " + e.getMessage());
            return null;
        }
    }


    private void submitReviews() {
        btnSubmitAllReviews.setEnabled(false);

        // ⭐ CHUYỂN LOGIC NẶNG SANG LUỒNG PHỤ ĐỂ TRÁNH ANR
        new Thread(() -> {
            List<ReviewItemModel> reviewsToSubmit = reviewAdapter.getReviewItems();

            // 1. Kiểm tra điều kiện (rating tối thiểu)
            for (ReviewItemModel model : reviewsToSubmit) {
                if (model.getRating() == 0) {
                    runOnUiThread(() -> {
                        Toast.makeText(ReviewActivity.this, "Vui lòng đánh giá sao cho tất cả sản phẩm.", Toast.LENGTH_SHORT).show();
                        btnSubmitAllReviews.setEnabled(true);
                    });
                    return;
                }
            }

            // 2. CHUẨN BỊ DỮ LIỆU CHO MULTIPART (Trên Background Thread)

            // a) Dữ liệu JSON (Text và Rating)
            int customerId = getCurrentCustomerId();
            ReviewSubmitRequest requestModel = new ReviewSubmitRequest(orderId, customerId, reviewsToSubmit);

            String jsonString = gson.toJson(requestModel);

            RequestBody reviewDataJson = RequestBody.create(MediaType.parse("application/json"), jsonString);

            // b) Chuẩn bị các tệp Ảnh và Video
            List<MultipartBody.Part> photoParts = new ArrayList<>();
            List<MultipartBody.Part> videoParts = new ArrayList<>();

            for (ReviewItemModel model : reviewsToSubmit) {

                // Xử lý Ảnh (Tên field PHP: photos[])
                if (model.getPhotoUris() != null) {
                    for (Uri uri : model.getPhotoUris()) {
                        MultipartBody.Part part = prepareFilePart("photos[]", uri);
                        if (part != null) {
                            photoParts.add(part);
                        }
                    }
                }

                // Xử lý Video (Tên field PHP: videos[])
                if (model.getVideoUris() != null) {
                    for (Uri uri : model.getVideoUris()) {
                        // Tên field PHP: videos[]
                        MultipartBody.Part part = prepareFilePart("videos[]", uri);
                        if (part != null) {
                            videoParts.add(part);
                        }
                    }
                }
            }

            // ⭐ 3. GỌI API MULTIPART (Trở lại Main Thread)
            runOnUiThread(() -> {
                api.submitReviewsMultipart(reviewDataJson, photoParts, videoParts).enqueue(new Callback<ApiResponse>() {
                    @Override
                    public void onResponse(Call<ApiResponse> call, Response<ApiResponse> response) {
                        if (response.isSuccessful() && response.body() != null && response.body().isSuccess()) {
                            Toast.makeText(ReviewActivity.this, response.body().getMessage(), Toast.LENGTH_LONG).show();
                            setResult(RESULT_OK);
                            finish();
                        } else {
                            String msg = response.body() != null ? response.body().getMessage() : "HTTP " + response.code();
                            Toast.makeText(ReviewActivity.this, "Gửi đánh giá thất bại: " + msg, Toast.LENGTH_LONG).show();
                            btnSubmitAllReviews.setEnabled(true);
                        }
                    }

                    @Override
                    public void onFailure(Call<ApiResponse> call, Throwable t) {
                        Log.e(TAG, "FATAL NETWORK ERROR: " + t.getMessage(), t);
                        Toast.makeText(ReviewActivity.this, "Lỗi kết nối khi gửi đánh giá.", Toast.LENGTH_SHORT).show();
                        btnSubmitAllReviews.setEnabled(true);
                    }
                });
            });
        }).start();
    }
}