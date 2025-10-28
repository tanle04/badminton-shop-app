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

import androidx.activity.result.ActivityResultLauncher; // ⭐ THÊM: Dùng ActivityResultLauncher
import androidx.activity.result.contract.ActivityResultContracts; // ⭐ THÊM
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
import com.example.badmintonshop.network.dto.OrderDto; // ⭐ SỬA: Dùng OrderDto
// ⭐ BỎ: import com.example.badmintonshop.network.dto.OrderDetailsListResponse;
import com.example.badmintonshop.network.dto.ReviewSubmitRequest;
import com.google.android.material.button.MaterialButton;
import com.google.android.material.appbar.MaterialToolbar;
import com.google.gson.Gson;

import java.io.File;
import java.io.IOException; // ⭐ THÊM
import java.io.InputStream; // ⭐ THÊM
import java.io.ByteArrayOutputStream; // ⭐ THÊM
import java.util.ArrayList;
import java.util.List;
import java.util.Locale;

import okhttp3.MediaType;
import okhttp3.MultipartBody;
import okhttp3.RequestBody;
import retrofit2.Call;
import retrofit2.Callback;
import retrofit2.Response;

// ⭐ THÊM
import android.os.Build;
import android.provider.OpenableColumns;


public class ReviewActivity extends AppCompatActivity implements ReviewAdapter.ReviewAdapterListener {

    private static final String TAG = "ReviewActivityDebug";
    private static final int MAX_PHOTOS = 5;
    private static final int MAX_VIDEOS = 1;

    // ⭐ SỬA: Bỏ các REQUEST_CODE cũ
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

    // ⭐ THÊM: ActivityResultLauncher cho Photo và Video
    private ActivityResultLauncher<Intent> photoPickerLauncher;
    private ActivityResultLauncher<Intent> videoPickerLauncher;

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
            getSupportActionBar().setTitle("Để lại đánh giá");
            getSupportActionBar().setDisplayHomeAsUpEnabled(true);
        }

        recyclerView = findViewById(R.id.recycler_review_items);
        btnSubmitAllReviews = findViewById(R.id.btn_submit_all_reviews);
        btnSubmitAllReviews.setEnabled(false);

        // ⭐ SỬA: Khởi tạo launchers
        initializeLaunchers();

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

    // ⭐ SỬA: Khởi tạo launchers
    private void initializeLaunchers() {
        photoPickerLauncher = registerForActivityResult(
                new ActivityResultContracts.StartActivityForResult(),
                result -> {
                    if (result.getResultCode() == RESULT_OK && result.getData() != null && currentReviewItemPosition != -1) {
                        ReviewItemModel currentModel = reviewItemsList.get(currentReviewItemPosition);
                        List<Uri> newUris = new ArrayList<>();

                        // Lấy nhiều ảnh
                        if (result.getData().getClipData() != null) {
                            int count = result.getData().getClipData().getItemCount();
                            for (int i = 0; i < count; i++) {
                                newUris.add(result.getData().getClipData().getItemAt(i).getUri());
                            }
                        } else if (result.getData().getData() != null) {
                            // Lấy 1 ảnh
                            newUris.add(result.getData().getData());
                        }

                        if (!newUris.isEmpty()) {
                            List<Uri> currentPhotos = currentModel.getPhotoUris();
                            currentPhotos.addAll(newUris); // Thêm ảnh mới vào

                            if (currentPhotos.size() > MAX_PHOTOS) {
                                // Cắt bớt nếu vượt quá giới hạn
                                currentPhotos = currentPhotos.subList(currentPhotos.size() - MAX_PHOTOS, currentPhotos.size());
                                Toast.makeText(this, "Chỉ được chọn tối đa " + MAX_PHOTOS + " ảnh.", Toast.LENGTH_LONG).show();
                            }

                            currentModel.setPhotoUris(currentPhotos);
                            Toast.makeText(this, "Đã thêm ảnh. Tổng cộng: " + currentPhotos.size() + " ảnh.", Toast.LENGTH_SHORT).show();
                            reviewAdapter.notifyItemChanged(currentReviewItemPosition);
                        }
                    }
                    currentReviewItemPosition = -1; // Reset
                }
        );

        videoPickerLauncher = registerForActivityResult(
                new ActivityResultContracts.StartActivityForResult(),
                result -> {
                    if (result.getResultCode() == RESULT_OK && result.getData() != null && currentReviewItemPosition != -1) {
                        Uri videoUri = result.getData().getData();
                        if (videoUri != null) {
                            ReviewItemModel currentModel = reviewItemsList.get(currentReviewItemPosition);
                            List<Uri> currentVideos = currentModel.getVideoUris();

                            currentVideos.clear(); // Luôn thay thế video cũ
                            currentVideos.add(videoUri);

                            currentModel.setVideoUris(currentVideos);
                            Toast.makeText(this, "Đã thêm 1 video.", Toast.LENGTH_SHORT).show();
                            reviewAdapter.notifyItemChanged(currentReviewItemPosition);
                        }
                    }
                    currentReviewItemPosition = -1; // Reset
                }
        );
    }


    // ⭐ HÀM KIỂM TRA VÀ YÊU CẦU QUYỀN (ĐÃ SỬA cho API 33+)
    private void requestStoragePermission(int position, boolean isPhoto) {
        String[] permissionsToRequest;

        // Kiểm tra phiên bản Android
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.TIRAMISU) {
            // Android 13 (API 33) trở lên
            permissionsToRequest = isPhoto ?
                    new String[]{android.Manifest.permission.READ_MEDIA_IMAGES} :
                    new String[]{android.Manifest.permission.READ_MEDIA_VIDEO};
        } else {
            // Android 12 trở xuống
            permissionsToRequest = new String[]{android.Manifest.permission.READ_EXTERNAL_STORAGE};
        }

        // Kiểm tra xem đã có quyền chưa
        boolean allGranted = true;
        for (String perm : permissionsToRequest) {
            if (ContextCompat.checkSelfPermission(this, perm) != PackageManager.PERMISSION_GRANTED) {
                allGranted = false;
                break;
            }
        }

        if (allGranted) {
            // Đã có quyền, tiếp tục
            if (isPhoto) {
                onPhotoIntent(position);
            } else {
                onVideoIntent(position);
            }
        } else {
            // Chưa có quyền, lưu trạng thái và yêu cầu
            currentReviewItemPosition = position;
            isAwaitingPhotoPermission = isPhoto;
            ActivityCompat.requestPermissions(this, permissionsToRequest, PERMISSION_REQUEST_CODE);
        }
    }

    // ⭐ XỬ LÝ KẾT QUẢ YÊU CẦU QUYỀN (ĐÃ SỬA)
    @Override
    public void onRequestPermissionsResult(int requestCode, @NonNull String[] permissions, @NonNull int[] grantResults) {
        super.onRequestPermissionsResult(requestCode, permissions, grantResults);

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
                // Đã được cấp quyền
                if (currentReviewItemPosition != -1) {
                    if (isAwaitingPhotoPermission) {
                        onPhotoIntent(currentReviewItemPosition);
                    } else {
                        onVideoIntent(currentReviewItemPosition);
                    }
                }
            } else {
                // Bị từ chối
                Toast.makeText(this, "Không thể gửi ảnh/video do thiếu quyền truy cập bộ nhớ.", Toast.LENGTH_LONG).show();
            }
        }
    }


    // ⭐ 1. PHƯƠNG THỨC XỬ LÝ CLICK (Không đổi)
    @Override
    public void onPhotoClicked(int position) {
        requestStoragePermission(position, true);
    }

    @Override
    public void onVideoClicked(int position) {
        requestStoragePermission(position, false);
    }

    // ⭐ HÀM CHỌN ẢNH (SỬA: Dùng launcher)
    private void onPhotoIntent(int position) {
        currentReviewItemPosition = position;
        Intent intent = new Intent(Intent.ACTION_PICK);
        intent.setType("image/*");
        intent.putExtra(Intent.EXTRA_ALLOW_MULTIPLE, true);
        photoPickerLauncher.launch(Intent.createChooser(intent, "Chọn ảnh sản phẩm"));
    }

    // ⭐ HÀM CHỌN VIDEO (SỬA: Dùng launcher)
    private void onVideoIntent(int position) {
        currentReviewItemPosition = position;
        Intent intent = new Intent(Intent.ACTION_PICK);
        intent.setType("video/*");
        videoPickerLauncher.launch(Intent.createChooser(intent, "Chọn video sản phẩm"));
    }

    // Logic onMediaDeleted (Giữ nguyên logic phức tạp của bạn, giả sử adapter gửi đúng)
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


    // ⭐ BỎ: onActivityResult (Đã thay thế bằng launchers)

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

    // ⭐ SỬA LẠI HOÀN TOÀN: Dùng OrderDto và customerId
    private void loadOrderDetailsForReview(int orderId) {
        Log.d(TAG, "Loading details for OrderID: " + orderId);
        btnSubmitAllReviews.setEnabled(false);

        int customerId = getCurrentCustomerId();
        if (customerId == -1) {
            Toast.makeText(this, "Lỗi xác thực người dùng.", Toast.LENGTH_SHORT).show();
            finish();
            return;
        }

        // ⭐ SỬA: Gọi đúng API với 2 tham số, nhận về Call<OrderDto>
        api.getOrderDetails(orderId, customerId).enqueue(new Callback<OrderDto>() {
            @Override
            public void onResponse(Call<OrderDto> call, Response<OrderDto> response) {
                // ⭐ SỬA: Logic nhận OrderDto
                if (response.isSuccessful() && response.body() != null) {
                    // Lấy danh sách item từ OrderDto
                    List<OrderDetailDto> orderDetails = response.body().getItems();

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
                    String msg = "Lỗi HTTP " + response.code();
                    Toast.makeText(ReviewActivity.this, "Lỗi tải chi tiết đơn hàng: " + msg, Toast.LENGTH_LONG).show();
                    Log.e(TAG, "API Load Details Failed: " + msg);
                    finish();
                }
            }

            @Override
            public void onFailure(Call<OrderDto> call, Throwable t) {
                btnSubmitAllReviews.setEnabled(true);
                Toast.makeText(ReviewActivity.this, "Lỗi kết nối mạng.", Toast.LENGTH_SHORT).show();
                Log.e(TAG, "Network Error loading order details: " + t.getMessage());
                finish();
            }
        });
    }

    // ⭐ BỎ: getRealPathFromURI (Không còn dùng)

    // ⭐ THÊM: Hàm trợ giúp để lấy tên file từ content:// URI
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
        // Thêm một phần ngẫu nhiên để tránh trùng lặp
        return System.currentTimeMillis() + "_" + result;
    }

    // ⭐ THÊM: Hàm trợ giúp để đọc InputStream từ URI và tạo RequestBody
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
            mimeType = "application/octet-stream"; // Mặc định
        }

        return RequestBody.create(MediaType.parse(mimeType), bytes);
    }


    // ⭐ SỬA: prepareFilePart (Dùng InputStream thay vì File path)
    private MultipartBody.Part prepareFilePart(String partName, Uri fileUri) {
        try {
            String fileName = getFileName(fileUri);
            RequestBody requestFile = createRequestBodyFromUri(fileUri);

            return MultipartBody.Part.createFormData(partName, fileName, requestFile);

        } catch (Exception e) {
            Log.e(TAG, "Lỗi chuẩn bị tệp: " + e.getMessage(), e);
            runOnUiThread(() -> Toast.makeText(ReviewActivity.this, "Lỗi đọc tệp: " + e.getMessage(), Toast.LENGTH_SHORT).show());
            return null;
        }
    }


    // ⭐ SỬA: submitReviews (Không đổi logic chính, nhưng hàm prepareFilePart đã được sửa)
    private void submitReviews() {
        btnSubmitAllReviews.setEnabled(false);
        Toast.makeText(this, "Đang chuẩn bị dữ liệu...", Toast.LENGTH_SHORT).show();

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

            Log.d(TAG, "Chuẩn bị xong: " + photoParts.size() + " ảnh, " + videoParts.size() + " video.");

            // ⭐ 3. GỌI API MULTIPART (Trở lại Main Thread)
            runOnUiThread(() -> {
                if(isFinishing()) return; // Kiểm tra nếu Activity đã bị đóng

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