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

import androidx.activity.result.ActivityResultLauncher;
import androidx.activity.result.contract.ActivityResultContracts;
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
// Import OrderDto vẫn được giữ lại vì OrderDetailDto có thể cần nó (hoặc không, nhưng không gây hại)
import com.example.badmintonshop.network.dto.OrderDto;
// ⭐ BẮT BUỘC: Import lớp Response mới
import com.example.badmintonshop.network.dto.ReviewDetailsResponse;
import com.example.badmintonshop.network.dto.ReviewSubmitRequest;
import com.google.android.material.button.MaterialButton;
import com.google.android.material.appbar.MaterialToolbar;
import com.google.gson.Gson;

import java.io.File;
import java.io.IOException;
import java.io.InputStream;
import java.io.ByteArrayOutputStream;
import java.util.ArrayList;
import java.util.List;
import java.util.Locale;

import okhttp3.MediaType;
import okhttp3.MultipartBody;
import okhttp3.RequestBody;
import retrofit2.Call;
import retrofit2.Callback;
import retrofit2.Response;

import android.os.Build;
import android.provider.OpenableColumns;


public class ReviewActivity extends AppCompatActivity implements ReviewAdapter.ReviewAdapterListener {

    private static final String TAG = "ReviewActivityDebug";
    private static final int MAX_PHOTOS = 5;
    private static final int MAX_VIDEOS = 1;
    private static final int PERMISSION_REQUEST_CODE = 1003;

    private int currentReviewItemPosition = -1;
    private boolean isAwaitingPhotoPermission = false;
    private boolean isLoading = false;

    private RecyclerView recyclerView;
    private MaterialButton btnSubmitAllReviews;
    private ReviewAdapter reviewAdapter;
    private int orderId;
    private ApiService api;
    private final Gson gson = new Gson();

    private final List<ReviewItemModel> reviewItemsList = new ArrayList<>();

    private ActivityResultLauncher<Intent> photoPickerLauncher;
    private ActivityResultLauncher<Intent> videoPickerLauncher;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        Log.d(TAG, "========================================");
        Log.d(TAG, "onCreate() started");
        Log.d(TAG, "========================================");

        setContentView(R.layout.activity_review);

        api = ApiClient.getApiService();
        Log.d(TAG, "ApiService initialized: " + (api != null ? "SUCCESS" : "NULL"));

        orderId = getIntent().getIntExtra("orderID", -1);
        Log.d(TAG, "Received orderID from Intent: " + orderId);

        if (orderId == -1) {
            Log.e(TAG, "❌ Invalid orderID. Finishing activity.");
            Toast.makeText(this, "Không tìm thấy ID đơn hàng.", Toast.LENGTH_SHORT).show();
            finish();
            return;
        }

        MaterialToolbar toolbar = findViewById(R.id.toolbar);
        setSupportActionBar(toolbar);
        if (getSupportActionBar() != null) {
            getSupportActionBar().setTitle("Để lại đánh giá");
            getSupportActionBar().setDisplayHomeAsUpEnabled(true);
        }
        Log.d(TAG, "Toolbar setup complete");

        recyclerView = findViewById(R.id.recycler_review_items);
        btnSubmitAllReviews = findViewById(R.id.btn_submit_all_reviews);
        btnSubmitAllReviews.setEnabled(false);
        Log.d(TAG, "Views initialized");

        initializeLaunchers();
        Log.d(TAG, "Activity Result Launchers initialized");

        setupRecyclerView();
        Log.d(TAG, "RecyclerView setup complete");

        loadOrderDetailsForReview(orderId);

        btnSubmitAllReviews.setOnClickListener(v -> {
            Log.d(TAG, "Submit button clicked");
            submitReviews();
        });

        Log.d(TAG, "onCreate() completed");
    }

    @Override
    public boolean onOptionsItemSelected(@NonNull MenuItem item) {
        if (item.getItemId() == android.R.id.home) {
            Log.d(TAG, "Back button pressed. Finishing activity.");
            finish();
            return true;
        }
        return super.onOptionsItemSelected(item);
    }

    // --- Các hàm xử lý media (initializeLaunchers, requestStoragePermission, v.v...) ---
    // --- (Giữ nguyên, không có lỗi) ---

    private void initializeLaunchers() {
        Log.d(TAG, "Initializing Activity Result Launchers...");

        photoPickerLauncher = registerForActivityResult(
                new ActivityResultContracts.StartActivityForResult(),
                result -> {
                    Log.d(TAG, "Photo picker result received. ResultCode: " + result.getResultCode());

                    if (result.getResultCode() == RESULT_OK && result.getData() != null && currentReviewItemPosition != -1) {
                        ReviewItemModel currentModel = reviewItemsList.get(currentReviewItemPosition);
                        List<Uri> newUris = new ArrayList<>();

                        if (result.getData().getClipData() != null) {
                            int count = result.getData().getClipData().getItemCount();
                            Log.d(TAG, "Multiple photos selected: " + count);
                            for (int i = 0; i < count; i++) {
                                newUris.add(result.getData().getClipData().getItemAt(i).getUri());
                            }
                        } else if (result.getData().getData() != null) {
                            Log.d(TAG, "Single photo selected");
                            newUris.add(result.getData().getData());
                        }

                        if (!newUris.isEmpty()) {
                            List<Uri> currentPhotos = currentModel.getPhotoUris();
                            int beforeSize = currentPhotos.size();
                            currentPhotos.addAll(newUris);

                            Log.d(TAG, "Photos before: " + beforeSize + ", adding: " + newUris.size());

                            if (currentPhotos.size() > MAX_PHOTOS) {
                                currentPhotos = currentPhotos.subList(currentPhotos.size() - MAX_PHOTOS, currentPhotos.size());
                                Log.w(TAG, "⚠️ Photo limit exceeded. Trimmed to: " + MAX_PHOTOS);
                                Toast.makeText(this, "Chỉ được chọn tối đa " + MAX_PHOTOS + " ảnh.", Toast.LENGTH_LONG).show();
                            }

                            currentModel.setPhotoUris(currentPhotos);
                            Log.d(TAG, "✅ Photos updated. Total: " + currentPhotos.size());
                            Toast.makeText(this, "Đã thêm ảnh. Tổng cộng: " + currentPhotos.size() + " ảnh.", Toast.LENGTH_SHORT).show();
                            reviewAdapter.notifyItemChanged(currentReviewItemPosition);
                        }
                    } else {
                        Log.w(TAG, "Photo picker cancelled or invalid data");
                    }
                    currentReviewItemPosition = -1;
                }
        );

        videoPickerLauncher = registerForActivityResult(
                new ActivityResultContracts.StartActivityForResult(),
                result -> {
                    Log.d(TAG, "Video picker result received. ResultCode: " + result.getResultCode());

                    if (result.getResultCode() == RESULT_OK && result.getData() != null && currentReviewItemPosition != -1) {
                        Uri videoUri = result.getData().getData();
                        if (videoUri != null) {
                            Log.d(TAG, "Video selected: " + videoUri.toString());

                            ReviewItemModel currentModel = reviewItemsList.get(currentReviewItemPosition);
                            List<Uri> currentVideos = currentModel.getVideoUris();

                            currentVideos.clear();
                            currentVideos.add(videoUri);

                            currentModel.setVideoUris(currentVideos);
                            Log.d(TAG, "✅ Video updated");
                            Toast.makeText(this, "Đã thêm 1 video.", Toast.LENGTH_SHORT).show();
                            reviewAdapter.notifyItemChanged(currentReviewItemPosition);
                        }
                    } else {
                        Log.w(TAG, "Video picker cancelled or invalid data");
                    }
                    currentReviewItemPosition = -1;
                }
        );

        Log.d(TAG, "Launchers initialized successfully");
    }

    private void requestStoragePermission(int position, boolean isPhoto) {
        Log.d(TAG, "Requesting storage permission. Position: " + position + ", isPhoto: " + isPhoto);

        String[] permissionsToRequest;

        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.TIRAMISU) {
            permissionsToRequest = isPhoto ?
                    new String[]{android.Manifest.permission.READ_MEDIA_IMAGES} :
                    new String[]{android.Manifest.permission.READ_MEDIA_VIDEO};
            Log.d(TAG, "Android 13+. Requesting: " + (isPhoto ? "READ_MEDIA_IMAGES" : "READ_MEDIA_VIDEO"));
        } else {
            permissionsToRequest = new String[]{android.Manifest.permission.READ_EXTERNAL_STORAGE};
            Log.d(TAG, "Android 12-. Requesting: READ_EXTERNAL_STORAGE");
        }

        boolean allGranted = true;
        for (String perm : permissionsToRequest) {
            if (ContextCompat.checkSelfPermission(this, perm) != PackageManager.PERMISSION_GRANTED) {
                allGranted = false;
                break;
            }
        }

        if (allGranted) {
            Log.d(TAG, "✅ Permission already granted");
            if (isPhoto) {
                onPhotoIntent(position);
            } else {
                onVideoIntent(position);
            }
        } else {
            Log.d(TAG, "⚠️ Permission not granted. Requesting...");
            currentReviewItemPosition = position;
            isAwaitingPhotoPermission = isPhoto;
            ActivityCompat.requestPermissions(this, permissionsToRequest, PERMISSION_REQUEST_CODE);
        }
    }

    @Override
    public void onRequestPermissionsResult(int requestCode, @NonNull String[] permissions, @NonNull int[] grantResults) {
        super.onRequestPermissionsResult(requestCode, permissions, grantResults);
        Log.d(TAG, "Permission result received. RequestCode: " + requestCode);

        if (requestCode == PERMISSION_REQUEST_CODE) {
            boolean allGranted = true;
            if (grantResults.length > 0) {
                for (int grantResult : grantResults) {
                    if (grantResult != PackageManager.PERMISSION_GRANTED) {
                        allGranted = false;
                        break;
                    }
                }
            } else {
                allGranted = false;
            }

            if (allGranted) {
                Log.d(TAG, "✅ Permission granted by user");
                if (currentReviewItemPosition != -1) {
                    if (isAwaitingPhotoPermission) {
                        onPhotoIntent(currentReviewItemPosition);
                    } else {
                        onVideoIntent(currentReviewItemPosition);
                    }
                }
            } else {
                Log.w(TAG, "❌ Permission denied by user");
                Toast.makeText(this, "Không thể gửi ảnh/video do thiếu quyền truy cập bộ nhớ.", Toast.LENGTH_LONG).show();
            }
        }
    }

    @Override
    public void onPhotoClicked(int position) {
        Log.d(TAG, "Photo button clicked. Position: " + position);
        requestStoragePermission(position, true);
    }

    @Override
    public void onVideoClicked(int position) {
        Log.d(TAG, "Video button clicked. Position: " + position);
        requestStoragePermission(position, false);
    }

    private void onPhotoIntent(int position) {
        Log.d(TAG, "Launching photo picker for position: " + position);
        currentReviewItemPosition = position;
        Intent intent = new Intent(Intent.ACTION_PICK);
        intent.setType("image/*");
        intent.putExtra(Intent.EXTRA_ALLOW_MULTIPLE, true);
        photoPickerLauncher.launch(Intent.createChooser(intent, "Chọn ảnh sản phẩm"));
    }

    private void onVideoIntent(int position) {
        Log.d(TAG, "Launching video picker for position: " + position);
        currentReviewItemPosition = position;
        Intent intent = new Intent(Intent.ACTION_PICK);
        intent.setType("video/*");
        videoPickerLauncher.launch(Intent.createChooser(intent, "Chọn video sản phẩm"));
    }

    @Override
    public void onMediaDeleted(int reviewPosition, int mediaPosition) {
        Log.d(TAG, "Media delete requested. ReviewPos: " + reviewPosition + ", MediaPos: " + mediaPosition);

        if (reviewPosition >= 0 && reviewPosition < reviewItemsList.size()) {
            ReviewItemModel model = reviewItemsList.get(reviewPosition);

            List<Uri> allMedia = new ArrayList<>();
            allMedia.addAll(model.getPhotoUris());
            allMedia.addAll(model.getVideoUris());

            Log.d(TAG, "Total media count: " + allMedia.size());

            if (mediaPosition >= 0 && mediaPosition < allMedia.size()) {
                Uri deletedUri = allMedia.get(mediaPosition);

                if (model.getVideoUris().contains(deletedUri)) {
                    model.getVideoUris().remove(deletedUri);
                    Log.d(TAG, "✅ Video removed");
                    Toast.makeText(this, "Đã xóa video.", Toast.LENGTH_SHORT).show();
                } else if (model.getPhotoUris().contains(deletedUri)) {
                    model.getPhotoUris().remove(deletedUri);
                    Log.d(TAG, "✅ Photo removed");
                    Toast.makeText(this, "Đã xóa ảnh.", Toast.LENGTH_SHORT).show();
                }
            } else {
                Log.e(TAG, "❌ Invalid media position");
            }
            reviewAdapter.notifyItemChanged(reviewPosition);
        } else {
            Log.e(TAG, "❌ Invalid review position");
        }
    }

    // --- (Hết phần xử lý media) ---

    private int getCurrentCustomerId() {
        SharedPreferences sp = getSharedPreferences("auth", MODE_PRIVATE);
        int customerId = sp.getInt("customerID", -1);
        Log.d(TAG, "Retrieved customerID from SharedPreferences: " + customerId);
        return customerId;
    }

    private void setupRecyclerView() {
        Log.d(TAG, "Setting up RecyclerView...");
        recyclerView.setLayoutManager(new LinearLayoutManager(this));
        reviewAdapter = new ReviewAdapter(this, reviewItemsList, this);
        recyclerView.setAdapter(reviewAdapter);
        Log.d(TAG, "RecyclerView configured with adapter");
    }

    private List<ReviewItemModel> convertOrderDetailsToReviewItems(List<OrderDetailDto> orderDetails) {
        Log.d(TAG, "Converting OrderDetails to ReviewItems...");
        Log.d(TAG, "Input OrderDetails count: " + (orderDetails != null ? orderDetails.size() : "null"));

        List<ReviewItemModel> items = new ArrayList<>();
        if (orderDetails != null) {
            for (OrderDetailDto detail : orderDetails) {
                Log.d(TAG, "Processing OrderDetailID: " + detail.getOrderDetailID() +
                        ", ProductID: " + detail.getProductID() +
                        ", isReviewed: " + detail.isReviewed());

                if (!detail.isReviewed()) {
                    items.add(new ReviewItemModel(detail));
                    Log.d(TAG, "✅ Added to review list");
                } else {
                    Log.d(TAG, "⏭️ Skipped (already reviewed)");
                }
            }
        }

        Log.d(TAG, "Conversion complete. ReviewItems count: " + items.size());
        return items;
    }

    // ⭐ BẮT ĐẦU SỬA LỖI
    private void loadOrderDetailsForReview(int orderId) {
        Log.d(TAG, "========================================");
        Log.d(TAG, "🔄 loadOrderDetailsForReview() called");
        Log.d(TAG, "OrderID: " + orderId);
        Log.d(TAG, "isLoading flag: " + isLoading);
        Log.d(TAG, "========================================");

        if (isLoading) {
            Log.w(TAG, "⚠️ Already loading! Skipping duplicate call.");
            return;
        }

        isLoading = true;
        btnSubmitAllReviews.setEnabled(false);

        int customerId = getCurrentCustomerId();
        if (customerId == -1) {
            Log.e(TAG, "❌ Invalid customerID. Cannot proceed.");
            Toast.makeText(this, "Lỗi xác thực người dùng.", Toast.LENGTH_SHORT).show();
            finish();
            return;
        }

        Log.d(TAG, "📡 Calling API: getOrderDetailsReview(orderID=" + orderId + ", customerID=" + customerId + ")");

        // ⭐ ĐÃ SỬA: Dùng getOrderDetailsReview và Callback<ReviewDetailsResponse>
        api.getOrderDetailsReview(orderId, customerId).enqueue(new Callback<ReviewDetailsResponse>() {

            // ⭐ ĐÃ SỬA: onResponse dùng Call<ReviewDetailsResponse>
            @Override
            public void onResponse(Call<ReviewDetailsResponse> call, Response<ReviewDetailsResponse> response) {
                isLoading = false;

                // KIỂM TRA RESPONSE MỚI (response.body().isSuccess())
                if (response.isSuccessful() && response.body() != null && response.body().isSuccess()) {

                    // Lấy danh sách từ đối tượng response mới (response.body().getOrderDetails())
                    List<OrderDetailDto> orderDetails = response.body().getOrderDetails();

                    Log.d(TAG, "✅ Response body received");
                    Log.d(TAG, "  - Items count: " + (orderDetails != null ? orderDetails.size() : "null"));

                    // Code logic còn lại giữ nguyên
                    List<ReviewItemModel> newReviews = convertOrderDetailsToReviewItems(orderDetails);

                    if (newReviews != null && !newReviews.isEmpty()) {
                        Log.d(TAG, "✅ Found " + newReviews.size() + " items to review");
                        reviewItemsList.clear();
                        reviewItemsList.addAll(newReviews);
                        reviewAdapter.updateData(reviewItemsList);
                        btnSubmitAllReviews.setEnabled(true);
                    } else {
                        Log.w(TAG, "⚠️ No items need review");
                        Toast.makeText(ReviewActivity.this, "Không có sản phẩm nào cần đánh giá.", Toast.LENGTH_SHORT).show();
                        finish();
                    }
                } else {
                    // Xử lý lỗi (API trả về isSuccess = false hoặc lỗi HTTP)
                    String msg = response.body() != null ? response.body().getMessage() : "Lỗi " + response.code();
                    Log.e(TAG, "❌ API call failed: " + msg);
                    Toast.makeText(ReviewActivity.this, "Lỗi: " + msg, Toast.LENGTH_LONG).show();
                    finish();
                }
            }

            // ⭐ ĐÃ SỬA: onFailure dùng Call<ReviewDetailsResponse> (Sửa lỗi crash)
            @Override
            public void onFailure(Call<ReviewDetailsResponse> call, Throwable t) {
                isLoading = false;
                Log.e(TAG, "🔴 NETWORK FAILURE", t);
                Toast.makeText(ReviewActivity.this, "Lỗi kết nối mạng: " + t.getMessage(), Toast.LENGTH_SHORT).show();
                finish();
            }
        });
    }
    // ⭐ KẾT THÚC SỬA LỖI


    // --- Các hàm xử lý file (getFileName, createRequestBodyFromUri, prepareFilePart) ---
    // --- (Giữ nguyên, không có lỗi) ---

    private String getFileName(Uri uri) {
        String result = null;
        if (uri.getScheme().equals("content")) {
            Cursor cursor = getContentResolver().query(uri, null, null, null, null);
            try {
                if (cursor != null && cursor.moveToFirst()) {
                    int index = cursor.getColumnIndex(OpenableColumns.DISPLAY_NAME);
                    if(index >= 0) {
                        result = cursor.getString(index);
                    }
                }
            } finally {
                if (cursor != null) cursor.close();
            }
        }
        if (result == null) {
            result = uri.getPath();
            int cut = result.lastIndexOf('/');
            if (cut != -1) {
                result = result.substring(cut + 1);
            }
        }
        return System.currentTimeMillis() + "_" + result;
    }

    private RequestBody createRequestBodyFromUri(Uri uri) throws IOException {
        InputStream inputStream = getContentResolver().openInputStream(uri);
        if (inputStream == null) {
            throw new IOException("Không thể mở InputStream cho URI: " + uri);
        }

        ByteArrayOutputStream byteStream = new ByteArrayOutputStream();
        byte[] buffer = new byte[4096];
        int bytesRead;
        while ((bytesRead = inputStream.read(buffer)) != -1) {
            byteStream.write(buffer, 0, bytesRead);
        }
        inputStream.close();
        byte[] bytes = byteStream.toByteArray();

        String mimeType = getContentResolver().getType(uri);
        if (mimeType == null) {
            mimeType = "application/octet-stream";
        }

        Log.d(TAG, "Created RequestBody. Size: " + bytes.length + " bytes, MimeType: " + mimeType);
        return RequestBody.create(MediaType.parse(mimeType), bytes);
    }

    private MultipartBody.Part prepareFilePart(String partName, Uri fileUri) {
        try {
            Log.d(TAG, "Preparing file part: " + partName + " for URI: " + fileUri);

            String fileName = getFileName(fileUri);
            RequestBody requestFile = createRequestBodyFromUri(fileUri);

            Log.d(TAG, "✅ File prepared: " + fileName);
            return MultipartBody.Part.createFormData(partName, fileName, requestFile);

        } catch (Exception e) {
            Log.e(TAG, "❌ Error preparing file: " + e.getMessage(), e);
            runOnUiThread(() -> Toast.makeText(ReviewActivity.this, "Lỗi đọc tệp: " + e.getMessage(), Toast.LENGTH_SHORT).show());
            return null;
        }
    }

    // --- (Hết phần xử lý file) ---


    // --- Hàm submitReviews (Giữ nguyên, không có lỗi) ---
    private void submitReviews() {
        Log.d(TAG, "========================================");
        Log.d(TAG, "🚀 SUBMIT REVIEWS STARTED");
        Log.d(TAG, "========================================");

        btnSubmitAllReviews.setEnabled(false);
        Toast.makeText(this, "Đang chuẩn bị dữ liệu...", Toast.LENGTH_SHORT).show();

        new Thread(() -> {
            Log.d(TAG, "Background thread started");

            List<ReviewItemModel> reviewsToSubmit = reviewAdapter.getReviewItems();
            Log.d(TAG, "Reviews to submit: " + reviewsToSubmit.size());

            for (ReviewItemModel model : reviewsToSubmit) {
                Log.d(TAG, "Checking review - OrderDetailID: " + model.getOrderDetail().getOrderDetailID() +
                        ", Rating: " + model.getRating());

                if (model.getRating() == 0) {
                    Log.w(TAG, "⚠️ Found review with 0 rating. Aborting.");
                    runOnUiThread(() -> {
                        Toast.makeText(ReviewActivity.this, "Vui lòng đánh giá sao cho tất cả sản phẩm.", Toast.LENGTH_SHORT).show();
                        btnSubmitAllReviews.setEnabled(true);
                    });
                    return;
                }
            }

            int customerId = getCurrentCustomerId();
            // ReviewSubmitRequest đã được thiết kế đúng (từ file bạn gửi trước)
            ReviewSubmitRequest requestModel = new ReviewSubmitRequest(orderId, customerId, reviewsToSubmit);

            String jsonString = gson.toJson(requestModel);
            Log.d(TAG, "📄 JSON Request Data:");
            Log.d(TAG, jsonString);

            RequestBody reviewDataJson = RequestBody.create(MediaType.parse("application/json"), jsonString);

            List<MultipartBody.Part> photoParts = new ArrayList<>();
            List<MultipartBody.Part> videoParts = new ArrayList<>();

            for (ReviewItemModel model : reviewsToSubmit) {
                if (model.getPhotoUris() != null) {
                    Log.d(TAG, "Processing photos for OrderDetailID: " + model.getOrderDetail().getOrderDetailID());
                    for (Uri uri : model.getPhotoUris()) {
                        MultipartBody.Part part = prepareFilePart("photos[]", uri);
                        if (part != null) {
                            photoParts.add(part);
                        }
                    }
                }

                if (model.getVideoUris() != null) {
                    Log.d(TAG, "Processing videos for OrderDetailID: " + model.getOrderDetail().getOrderDetailID());
                    for (Uri uri : model.getVideoUris()) {
                        MultipartBody.Part part = prepareFilePart("videos[]", uri);
                        if (part != null) {
                            videoParts.add(part);
                        }
                    }
                }
            }

            Log.d(TAG, "📦 Total prepared: " + photoParts.size() + " photos, " + videoParts.size() + " videos");

            runOnUiThread(() -> {
                if(isFinishing()) {
                    Log.w(TAG, "⚠️ Activity is finishing. Aborting API call.");
                    return;
                }

                Log.d(TAG, "📡 Calling submitReviewsMultipart API...");
                Toast.makeText(ReviewActivity.this, "Đang tải lên đánh giá...", Toast.LENGTH_SHORT).show();

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
                            Log.e(TAG, "Submit review failed: " + msg);
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