package com.example.badmintonshop.ui;

import android.content.Intent;
import android.content.pm.PackageManager;
import android.database.Cursor;
import android.graphics.Bitmap;
import android.net.Uri;
import android.os.Build;
import android.os.Bundle;
import android.provider.MediaStore;
import android.provider.OpenableColumns;
import android.util.Base64;
import android.util.Log;
import android.view.View;
import android.widget.Button;
import android.widget.EditText;
import android.widget.ProgressBar;
import android.widget.Toast;

import androidx.activity.result.ActivityResultLauncher;
import androidx.activity.result.contract.ActivityResultContracts;
import androidx.annotation.NonNull;
import androidx.appcompat.app.AppCompatActivity;
import androidx.core.app.ActivityCompat;
import androidx.core.content.ContextCompat;
import androidx.recyclerview.widget.LinearLayoutManager;
import androidx.recyclerview.widget.RecyclerView;

import com.example.badmintonshop.R;
import com.example.badmintonshop.adapter.RefundItemsAdapter;
import com.example.badmintonshop.adapter.RefundMediaAdapter; // ⭐ THAY THẾ
import com.example.badmintonshop.network.ApiClient;
import com.example.badmintonshop.network.ApiService;
import com.example.badmintonshop.network.dto.ApiResponse;
import com.example.badmintonshop.network.dto.OrderDetailDto;
import com.example.badmintonshop.network.dto.OrderDetailsListResponse;
import com.example.badmintonshop.network.dto.OrderDetailsListResponse;
import com.example.badmintonshop.network.dto.RefundRequestBody;
import com.google.gson.Gson; // ⭐ THÊM

import java.io.ByteArrayOutputStream;
import java.io.IOException;
import java.io.InputStream;
import java.util.ArrayList;
import java.util.List;

import okhttp3.MediaType; // ⭐ THÊM
import okhttp3.MultipartBody; // ⭐ THÊM
import okhttp3.RequestBody; // ⭐ THÊM
import retrofit2.Call;
import retrofit2.Callback;
import retrofit2.Response;

// ⭐ THÊM IMPLEMENTS CHO LISTENER MỚI
public class RequestRefundActivity extends AppCompatActivity implements RefundMediaAdapter.OnMediaRemoveListener {

    private static final String TAG = "RefundActivity";
    private int orderId;
    private int customerId;

    // ⭐ THÊM HẰNG SỐ (Copy từ ReviewActivity)
    private static final int MAX_PHOTOS = 5;
    private static final int MAX_VIDEOS = 1;
    private static final int PERMISSION_REQUEST_CODE_READ = 1004;
    private boolean isAwaitingPhotoPermission = false; // Dùng để biết xin quyền cho ảnh hay video

    private ApiService api;
    private Gson gson = new Gson(); // ⭐ THÊM
    private RecyclerView recyclerItems;
    private RecyclerView recyclerMedia; // ⭐ ĐỔI TÊN
    private EditText etOverallReason;
    private Button btnAddImage, btnAddVideo, btnSubmit; // ⭐ THÊM
    private ProgressBar progressBar;

    private RefundItemsAdapter itemsAdapter;
    private RefundMediaAdapter mediaAdapter; // ⭐ ĐỔI TÊN

    // ⭐ ĐỔI TỪ Base64 sang Uri
    private final List<Uri> photoUris = new ArrayList<>();
    private final List<Uri> videoUris = new ArrayList<>();

    private ActivityResultLauncher<Intent> photoPickerLauncher;
    private ActivityResultLauncher<Intent> videoPickerLauncher; // ⭐ THÊM

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_request_refund);

        orderId = getIntent().getIntExtra("ORDER_ID", -1);
        customerId = getIntent().getIntExtra("CUSTOMER_ID", -1);

        if (orderId == -1 || customerId == -1) {
            Toast.makeText(this, "Lỗi: Thiếu ID đơn hàng hoặc khách hàng.", Toast.LENGTH_LONG).show();
            finish();
            return;
        }

        api = ApiClient.getApiService();

        // Khởi tạo Views
        recyclerItems = findViewById(R.id.recycler_refund_items);
        recyclerMedia = findViewById(R.id.recycler_refund_media); // ⭐ SỬA ID
        etOverallReason = findViewById(R.id.et_refund_reason_overall);
        btnAddImage = findViewById(R.id.btn_add_refund_image);
        btnAddVideo = findViewById(R.id.btn_add_refund_video); // ⭐ VIEW MỚI
        btnSubmit = findViewById(R.id.btn_submit_refund);
        progressBar = findViewById(R.id.progress_bar);

        initializeLaunchers(); // ⭐ THAY ĐỔI: Khởi tạo cả 2 launcher
        setupRecyclerViews();

        btnAddImage.setOnClickListener(v -> requestStoragePermission(true)); // ⭐ Sửa: Xin quyền
        btnAddVideo.setOnClickListener(v -> requestStoragePermission(false)); // ⭐ Sửa: Xin quyền
        btnSubmit.setOnClickListener(v -> submitRefundRequest());

        fetchOrderDetails();
    }

    // --- ⭐ BẮT ĐẦU BLOCKS CODE COPY TỪ REVIEWACTIVITY ---

    // 1. KHỞI TẠO LAUNCHERS (Đã sửa đổi)
    private void initializeLaunchers() {
        Log.d(TAG, "Initializing Activity Result Launchers...");

        photoPickerLauncher = registerForActivityResult(
                new ActivityResultContracts.StartActivityForResult(),
                result -> {
                    Log.d(TAG, "Photo picker result received. ResultCode: " + result.getResultCode());
                    if (result.getResultCode() == RESULT_OK && result.getData() != null) {
                        List<Uri> newUris = new ArrayList<>();
                        if (result.getData().getClipData() != null) { // Chọn nhiều ảnh
                            int count = result.getData().getClipData().getItemCount();
                            for (int i = 0; i < count; i++) {
                                newUris.add(result.getData().getClipData().getItemAt(i).getUri());
                            }
                        } else if (result.getData().getData() != null) { // Chọn 1 ảnh
                            newUris.add(result.getData().getData());
                        }

                        int currentSize = photoUris.size();
                        photoUris.addAll(newUris);
                        if (photoUris.size() > MAX_PHOTOS) {
                            photoUris.subList(0, photoUris.size() - MAX_PHOTOS).clear(); // Giữ 5 ảnh cuối
                            Toast.makeText(this, "Chỉ được chọn tối đa " + MAX_PHOTOS + " ảnh.", Toast.LENGTH_SHORT).show();
                        }

                        mediaAdapter.updateMediaUris(); // Cập nhật adapter
                        Log.d(TAG, "✅ Photos updated. Total: " + photoUris.size());
                    }
                }
        );

        videoPickerLauncher = registerForActivityResult(
                new ActivityResultContracts.StartActivityForResult(),
                result -> {
                    Log.d(TAG, "Video picker result received. ResultCode: " + result.getResultCode());
                    if (result.getResultCode() == RESULT_OK && result.getData() != null && result.getData().getData() != null) {
                        videoUris.clear(); // Chỉ cho phép 1 video
                        videoUris.add(result.getData().getData());
                        mediaAdapter.updateMediaUris(); // Cập nhật adapter
                        Log.d(TAG, "✅ Video updated. Total: " + videoUris.size());
                    }
                }
        );
    }

    // 2. LOGIC XIN QUYỀN (Đã sửa đổi)
    private void requestStoragePermission(boolean isPhoto) {
        Log.d(TAG, "Requesting storage permission. isPhoto: " + isPhoto);
        isAwaitingPhotoPermission = isPhoto; // Lưu lại hành động

        String[] permissionsToRequest;
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.TIRAMISU) { // Android 13+
            permissionsToRequest = isPhoto ?
                    new String[]{android.Manifest.permission.READ_MEDIA_IMAGES} :
                    new String[]{android.Manifest.permission.READ_MEDIA_VIDEO};
        } else { // Android 12-
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
            Log.d(TAG, "✅ Permission already granted");
            launchPicker(isPhoto); // Mở picker
        } else {
            Log.d(TAG, "⚠️ Permission not granted. Requesting...");
            ActivityCompat.requestPermissions(this, permissionsToRequest, PERMISSION_REQUEST_CODE_READ);
        }
    }

    // 3. XỬ LÝ KẾT QUẢ XIN QUYỀN
    @Override
    public void onRequestPermissionsResult(int requestCode, @NonNull String[] permissions, @NonNull int[] grantResults) {
        super.onRequestPermissionsResult(requestCode, permissions, grantResults);
        Log.d(TAG, "Permission result received. RequestCode: " + requestCode);

        if (requestCode == PERMISSION_REQUEST_CODE_READ) {
            boolean allGranted = grantResults.length > 0;
            for (int grantResult : grantResults) {
                if (grantResult != PackageManager.PERMISSION_GRANTED) {
                    allGranted = false;
                    break;
                }
            }

            if (allGranted) {
                Log.d(TAG, "✅ Permission granted by user");
                launchPicker(isAwaitingPhotoPermission); // Mở picker
            } else {
                Log.w(TAG, "❌ Permission denied by user");
                Toast.makeText(this, "Không thể chọn media do thiếu quyền.", Toast.LENGTH_LONG).show();
            }
        }
    }

    // 4. MỞ PICKER (Đã sửa đổi)
    private void launchPicker(boolean isPhoto) {
        if (isPhoto) {
            if (photoUris.size() >= MAX_PHOTOS) {
                Toast.makeText(this, "Đã đạt tối đa " + MAX_PHOTOS + " ảnh", Toast.LENGTH_SHORT).show();
                return;
            }
            Log.d(TAG, "Launching photo picker");
            Intent intent = new Intent(Intent.ACTION_PICK);
            intent.setType("image/*");
            intent.putExtra(Intent.EXTRA_ALLOW_MULTIPLE, true);
            photoPickerLauncher.launch(Intent.createChooser(intent, "Chọn ảnh (tối đa " + MAX_PHOTOS + ")"));
        } else {
            if (videoUris.size() >= MAX_VIDEOS) {
                Toast.makeText(this, "Chỉ được chọn " + MAX_VIDEOS + " video", Toast.LENGTH_SHORT).show();
                return;
            }
            Log.d(TAG, "Launching video picker");
            Intent intent = new Intent(Intent.ACTION_PICK);
            intent.setType("video/*");
            videoPickerLauncher.launch(Intent.createChooser(intent, "Chọn 1 video"));
        }
    }

    // 5. CÁC HÀM HELPER VỀ FILE
    private String getFileName(Uri uri) {
        String result = null;
        if (uri.getScheme().equals("content")) {
            Cursor cursor = getContentResolver().query(uri, null, null, null, null);
            try {
                if (cursor != null && cursor.moveToFirst()) {
                    int index = cursor.getColumnIndex(OpenableColumns.DISPLAY_NAME);
                    if(index >= 0) result = cursor.getString(index);
                }
            } finally {
                if (cursor != null) cursor.close();
            }
        }
        if (result == null) {
            result = uri.getPath();
            int cut = result.lastIndexOf('/');
            if (cut != -1) result = result.substring(cut + 1);
        }
        return System.currentTimeMillis() + "_" + result;
    }

    private RequestBody createRequestBodyFromUri(Uri uri) throws IOException {
        InputStream inputStream = getContentResolver().openInputStream(uri);
        if (inputStream == null) throw new IOException("Không thể mở InputStream cho URI: " + uri);

        ByteArrayOutputStream byteStream = new ByteArrayOutputStream();
        byte[] buffer = new byte[4096];
        int bytesRead;
        while ((bytesRead = inputStream.read(buffer)) != -1) {
            byteStream.write(buffer, 0, bytesRead);
        }
        inputStream.close();
        byte[] bytes = byteStream.toByteArray();

        String mimeType = getContentResolver().getType(uri);
        if (mimeType == null) mimeType = "application/octet-stream";

        return RequestBody.create(MediaType.parse(mimeType), bytes);
    }

    private MultipartBody.Part prepareFilePart(String partName, Uri fileUri) {
        try {
            String fileName = getFileName(fileUri);
            RequestBody requestFile = createRequestBodyFromUri(fileUri);
            return MultipartBody.Part.createFormData(partName, fileName, requestFile);
        } catch (Exception e) {
            Log.e(TAG, "❌ Error preparing file: " + e.getMessage(), e);
            runOnUiThread(() -> Toast.makeText(this, "Lỗi đọc tệp: " + e.getMessage(), Toast.LENGTH_SHORT).show());
            return null;
        }
    }

    // --- ⭐ KẾT THÚC BLOCKS CODE COPY TỪ REVIEWACTIVITY ---


    private void setupRecyclerViews() {
        // RecyclerView cho sản phẩm (code cũ)
        recyclerItems.setLayoutManager(new LinearLayoutManager(this));

        // RecyclerView cho media (ảnh/video)
        recyclerMedia.setLayoutManager(new LinearLayoutManager(this, LinearLayoutManager.HORIZONTAL, false));
        // Khởi tạo adapter media và gán listener là "this" (Activity này)
        mediaAdapter = new RefundMediaAdapter(this, photoUris, videoUris, this);
        recyclerMedia.setAdapter(mediaAdapter);
    }

    // Xử lý khi bấm nút 'X' trên media
    @Override
    public void onMediaRemoved(Uri uri) {
        if (photoUris.contains(uri)) {
            photoUris.remove(uri);
        } else if (videoUris.contains(uri)) {
            videoUris.remove(uri);
        }
        mediaAdapter.updateMediaUris(); // Cập nhật lại adapter
    }

    private void fetchOrderDetails() {
        // (Hàm này giữ nguyên như code cũ, không thay đổi)
        setLoading(true);
        api.getOrderDetailsForReview(orderId, customerId).enqueue(new Callback<OrderDetailsListResponse>() {
            @Override
            public void onResponse(@NonNull Call<OrderDetailsListResponse> call, @NonNull Response<OrderDetailsListResponse> response) {
                setLoading(false);
                if (response.isSuccessful() && response.body() != null && response.body().isSuccess()) {
                    List<OrderDetailDto> details = response.body().getOrderDetails();
                    if (details != null && !details.isEmpty()) {
                        itemsAdapter = new RefundItemsAdapter(RequestRefundActivity.this, details);
                        recyclerItems.setAdapter(itemsAdapter);
                    } else {
                        Toast.makeText(RequestRefundActivity.this, "Không tìm thấy chi tiết đơn hàng.", Toast.LENGTH_SHORT).show();
                    }
                } else {
                    String msg = parseErrorMessage(response); // Dùng hàm parse lỗi đã thêm ở bước trước
                    Log.e(TAG, "Lỗi fetchOrderDetails: " + msg);
                    Toast.makeText(RequestRefundActivity.this, "Lỗi: " + msg, Toast.LENGTH_LONG).show();
                    finish();
                }
            }
            @Override
            public void onFailure(@NonNull Call<OrderDetailsListResponse> call, @NonNull Throwable t) {
                setLoading(false);
                Log.e(TAG, "Lỗi fetchOrderDetails: ", t);
                Toast.makeText(RequestRefundActivity.this, "Lỗi mạng: " + t.getMessage(), Toast.LENGTH_SHORT).show();
                finish();
            }
        });
    }

    // ⭐ HÀM SUBMIT ĐƯỢC VIẾT LẠI HOÀN TOÀN
    private void submitRefundRequest() {
        if (itemsAdapter == null) {
            Toast.makeText(this, "Chưa tải được sản phẩm, vui lòng đợi", Toast.LENGTH_SHORT).show();
            return;
        }
        List<RefundRequestBody.RefundItem> selectedItems = itemsAdapter.getSelectedRefundItems();
        String overallReason = etOverallReason.getText().toString().trim();

        if (selectedItems.isEmpty()) {
            Toast.makeText(this, "Vui lòng chọn ít nhất 1 sản phẩm để trả", Toast.LENGTH_SHORT).show();
            return;
        }
        if (overallReason.isEmpty()) {
            Toast.makeText(this, "Vui lòng nhập lý do chung", Toast.LENGTH_SHORT).show();
            return;
        }
        if (photoUris.isEmpty() && videoUris.isEmpty()) {
            Toast.makeText(this, "Vui lòng thêm ít nhất 1 ảnh hoặc video bằng chứng", Toast.LENGTH_SHORT).show();
            return;
        }

        setLoading(true);
        Toast.makeText(this, "Đang chuẩn bị dữ liệu...", Toast.LENGTH_SHORT).show();

        // Chạy trên thread mới để chuẩn bị file
        new Thread(() -> {
            // 1. Chuẩn bị phần JSON
            // (Sử dụng DTO đã bỏ trường 'images')
            RefundRequestBody requestModel = new RefundRequestBody(
                    customerId,
                    orderId,
                    overallReason,
                    selectedItems
            );
            String jsonString = gson.toJson(requestModel);
            RequestBody refundDataJson = RequestBody.create(MediaType.parse("application/json"), jsonString);
            Log.d(TAG, "JSON data: " + jsonString);

            // 2. Chuẩn bị phần Files (Ảnh)
            List<MultipartBody.Part> photoParts = new ArrayList<>();
            for (Uri uri : photoUris) {
                MultipartBody.Part part = prepareFilePart("photos[]", uri);
                if (part != null) photoParts.add(part);
            }
            Log.d(TAG, "Prepared " + photoParts.size() + " photo parts.");

            // 3. Chuẩn bị phần Files (Video)
            List<MultipartBody.Part> videoParts = new ArrayList<>();
            for (Uri uri : videoUris) {
                MultipartBody.Part part = prepareFilePart("videos[]", uri);
                if (part != null) videoParts.add(part);
            }
            Log.d(TAG, "Prepared " + videoParts.size() + " video parts.");

            // 4. Gọi API trên Main Thread
            runOnUiThread(() -> {
                if (isFinishing()) return;

                Toast.makeText(this, "Đang tải lên bằng chứng...", Toast.LENGTH_SHORT).show();

                // ⭐ GỌI API MULTIPART MỚI
                api.submitRefundRequestMultipart(refundDataJson, photoParts, videoParts).enqueue(new Callback<ApiResponse>() {
                    @Override
                    public void onResponse(@NonNull Call<ApiResponse> call, @NonNull Response<ApiResponse> response) {
                        setLoading(false);
                        if (response.isSuccessful() && response.body() != null && response.body().isSuccess()) {
                            Toast.makeText(RequestRefundActivity.this, "Gửi yêu cầu thành công!", Toast.LENGTH_LONG).show();
                            setResult(RESULT_OK);
                            finish();
                        } else {
                            String error = parseErrorMessage(response); // Dùng lại hàm parse lỗi
                            Log.e(TAG, "Lỗi submitRefundRequest: " + error);
                            Toast.makeText(RequestRefundActivity.this, "Lỗi: " + error, Toast.LENGTH_LONG).show();
                        }
                    }

                    @Override
                    public void onFailure(@NonNull Call<ApiResponse> call, @NonNull Throwable t) {
                        setLoading(false);
                        Log.e(TAG, "Lỗi submitRefundRequest: ", t);
                        Toast.makeText(RequestRefundActivity.this, "Lỗi mạng: " + t.getMessage(), Toast.LENGTH_LONG).show();
                    }
                });
            });
        }).start();
    }

    private void setLoading(boolean isLoading) {
        if (isLoading) {
            progressBar.setVisibility(View.VISIBLE);
            btnSubmit.setEnabled(false);
            btnAddImage.setEnabled(false);
            btnAddVideo.setEnabled(false); // ⭐ VÔ HIỆU HÓA NÚT MỚI
        } else {
            progressBar.setVisibility(View.GONE);
            btnSubmit.setEnabled(true);
            btnAddImage.setEnabled(true);
            btnAddVideo.setEnabled(true); // ⭐ KÍCH HOẠT NÚT MỚI
        }
    }

    // Hàm parseErrorMessage (đã có từ lần trước)
    // ⭐ THAY THẾ TOÀN BỘ HÀM NÀY
    private String parseErrorMessage(Response<?> response) {
        String defaultError = "Lỗi không xác định (Code: " + response.code() + ")";

        if (response.errorBody() == null) {
            // Thử đọc body (cho trường hợp isSuccess = false)
            if (response.body() instanceof ApiResponse) {
                ApiResponse apiResponse = (ApiResponse) response.body();
                if (apiResponse != null && apiResponse.getMessage() != null && !apiResponse.getMessage().isEmpty()) {
                    return apiResponse.getMessage();
                }
            }
            return defaultError;
        }

        // Bắt đầu xử lý errorBody
        try {
            String errorBodyString = response.errorBody().string();
            Log.e(TAG, "Error Body: " + errorBodyString); // Log toàn bộ lỗi

            // KIỂM TRA MỚI: Nếu errorBody là HTML, không parse JSON
            if (errorBodyString != null && errorBodyString.trim().startsWith("<!DOCTYPE html")) {
                Log.e(TAG, "Server returned an HTML error page (404 or 500)");
                return "Lỗi máy chủ hoặc không tìm thấy API. (Code: " + response.code() + ")";
            }

            // Nếu không phải HTML, thử parse JSON
            com.google.gson.Gson gson = new com.google.gson.Gson();
            ApiResponse errorResponse = gson.fromJson(errorBodyString, ApiResponse.class);

            if (errorResponse != null && errorResponse.getMessage() != null && !errorResponse.getMessage().isEmpty()) {
                return errorResponse.getMessage() + " (Code: " + response.code() + ")";
            } else {
                return defaultError;
            }

        } catch (com.google.gson.JsonSyntaxException e) { // Lỗi nếu nó không phải JSON
            Log.e(TAG, "Error parsing error body: Not a JSON response", e);
            return "Lỗi phân tích cú pháp phản hồi từ máy chủ. (Code: " + response.code() + ")";

        } catch (Exception e) { // Các lỗi khác (ví dụ: IOException)
            Log.e(TAG, "Error reading error body", e);
            return defaultError;
        }
    }
}