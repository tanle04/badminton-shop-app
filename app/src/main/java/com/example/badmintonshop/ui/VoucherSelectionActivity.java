package com.example.badmintonshop.ui;

import android.content.Intent;
import android.content.SharedPreferences;
import android.os.Bundle;
import android.util.Log;
import android.widget.TextView;
import android.widget.Toast;
import androidx.appcompat.app.AppCompatActivity;
import androidx.recyclerview.widget.LinearLayoutManager;
import androidx.recyclerview.widget.RecyclerView;
import com.example.badmintonshop.R;
import com.example.badmintonshop.adapter.VoucherAdapter;
import com.example.badmintonshop.network.ApiClient;
import com.example.badmintonshop.network.ApiService;
import com.example.badmintonshop.network.dto.ApiResponse;
import com.example.badmintonshop.network.dto.VoucherDto;
import com.example.badmintonshop.network.dto.VoucherListResponse;
import com.google.android.material.appbar.MaterialToolbar;
import com.google.android.material.button.MaterialButton;
import com.google.android.material.textfield.TextInputEditText;
import java.util.ArrayList;
import java.util.List;
import retrofit2.Call;
import retrofit2.Callback;
import retrofit2.Response;

public class VoucherSelectionActivity extends AppCompatActivity {

    private RecyclerView recyclerView;
    private MaterialToolbar toolbar;
    private MaterialButton btnApplyVoucher;
    private VoucherAdapter adapter;
    private ApiService api;
    private TextView tvCancelVoucher;

    private TextInputEditText etVoucherCodeInput;
    private MaterialButton btnRedeemCode;

    private static final String TAG = "VOUCHER_FLOW_DEBUG";

    private List<VoucherDto> availableVouchers = new ArrayList<>();
    private VoucherDto selectedVoucher = null;
    private double subtotal = 0.0;
    private int initialSelectedVoucherId = -1;

    // ⭐ BIẾN MỚI: Lưu trữ mã voucher vừa redeem thành công
    private String lastRedeemedCode = null;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_voucher_selection);

        api = ApiClient.getApiService();

        // Lấy dữ liệu subtotal và voucher đã chọn ban đầu
        Intent intent = getIntent();
        subtotal = intent.getDoubleExtra("SUBTOTAL", 0.0);
        selectedVoucher = (VoucherDto) intent.getSerializableExtra("SELECTED_VOUCHER");
        if (selectedVoucher != null) {
            initialSelectedVoucherId = selectedVoucher.getVoucherID();
            Log.d(TAG, "Initial Selected Voucher ID: " + initialSelectedVoucherId);
        }

        bindViews();
        setupToolbar();
        setupRecyclerView();

        // Log 1: Giá trị subtotal
        Log.d(TAG, "Subtotal received: " + subtotal);

        fetchVouchers();

        btnApplyVoucher.setOnClickListener(v -> applyVoucher());
        // Xử lý sự kiện cho nút Redeem
        btnRedeemCode.setOnClickListener(v -> handleRedeemCode());

        updateApplyButtonText();
    }

    private void bindViews() {
        toolbar = findViewById(R.id.toolbar);
        recyclerView = findViewById(R.id.recycler_vouchers);
        btnApplyVoucher = findViewById(R.id.btn_apply_voucher);
        tvCancelVoucher = findViewById(R.id.tv_cancel_voucher);

        // ÁNH XẠ VIEWS MỚI
        etVoucherCodeInput = findViewById(R.id.et_voucher_code_input);
        btnRedeemCode = findViewById(R.id.btn_redeem_code);

        // Thêm listener cho "Không dùng Voucher"
        tvCancelVoucher.setOnClickListener(v -> {
            Log.d(TAG, "User chose to cancel/remove voucher.");
            selectedVoucher = null;
            // Reset lựa chọn trong Adapter
            adapter.resetSelection();
            // Cập nhật text nút
            updateApplyButtonText();
        });
    }

    private void setupToolbar() {
        toolbar.setNavigationOnClickListener(v -> finish());
    }

    private void setupRecyclerView() {
        recyclerView.setLayoutManager(new LinearLayoutManager(this));

        // Khởi tạo adapter với listener để cập nhật voucher được chọn
        adapter = new VoucherAdapter(this, availableVouchers, initialSelectedVoucherId, voucher -> {
            selectedVoucher = voucher;
            Log.d(TAG, "Voucher selected: " + (voucher != null ? voucher.getVoucherCode() : "null"));
            updateApplyButtonText();
        });
        recyclerView.setAdapter(adapter);
    }

    private void updateApplyButtonText() {
        if (selectedVoucher != null) {
            btnApplyVoucher.setText("Đồng ý (Đã chọn 1 Voucher)");
        } else {
            btnApplyVoucher.setText("Đồng ý (Không dùng Voucher)");
        }
    }

    // ⭐ HÀM MỚI: Xử lý việc nhập mã và gán vào tài khoản
    private void handleRedeemCode() {
        String code = etVoucherCodeInput.getText() != null ? etVoucherCodeInput.getText().toString().trim().toUpperCase() : "";
        int customerId = getCurrentCustomerId();

        if (code.isEmpty()) {
            Toast.makeText(this, "Vui lòng nhập mã giảm giá.", Toast.LENGTH_SHORT).show();
            return;
        }
        if (customerId <= 0) {
            Toast.makeText(this, "Bạn cần đăng nhập để thêm mã giảm giá.", Toast.LENGTH_SHORT).show();
            return;
        }

        btnRedeemCode.setEnabled(false);
        Log.d(TAG, "Attempting to redeem code: " + code);

        api.redeemVoucher(code, customerId).enqueue(new Callback<ApiResponse>() {
            @Override
            public void onResponse(Call<ApiResponse> call, Response<ApiResponse> response) {
                btnRedeemCode.setEnabled(true);

                if (response.isSuccessful() && response.body() != null) {
                    if (response.body().isSuccess()) {
                        Toast.makeText(VoucherSelectionActivity.this, response.body().getMessage(), Toast.LENGTH_LONG).show();
                        Log.i(TAG, "Redeem successful: " + response.body().getMessage());

                        // ⭐ LƯU MÃ VỪA REDEEM ĐỂ CHỌN SAU KHI TẢI LẠI
                        lastRedeemedCode = code;

                        fetchVouchers();
                        etVoucherCodeInput.setText("");
                    } else {
                        String msg = response.body().getMessage();
                        Toast.makeText(VoucherSelectionActivity.this, "Lỗi: " + msg, Toast.LENGTH_LONG).show();
                        Log.e(TAG, "Redeem failed (Logic): " + msg);
                    }
                } else {
                    Toast.makeText(VoucherSelectionActivity.this, "Lỗi server. Mã HTTP: " + response.code(), Toast.LENGTH_SHORT).show();
                    Log.e(TAG, "Redeem failed (HTTP): " + response.code());
                }
            }

            @Override
            public void onFailure(Call<ApiResponse> call, Throwable t) {
                btnRedeemCode.setEnabled(true);
                Toast.makeText(VoucherSelectionActivity.this, "Lỗi kết nối mạng.", Toast.LENGTH_SHORT).show();
                Log.e(TAG, "Redeem network error: ", t);
            }
        });
    }

    private void fetchVouchers() {
        int customerId = getCurrentCustomerId();

        Log.d(TAG, "Attempting to fetch vouchers. CustomerID: " + customerId);

        if (customerId == -1) {
            Toast.makeText(this, "Lỗi: Không tìm thấy thông tin khách hàng. (ID = -1)", Toast.LENGTH_SHORT).show();
            Log.e(TAG, "Error: customerID is -1. Check authentication status.");
            return;
        }

        Log.d(TAG, "Calling API: getApplicableVouchers(customerId: " + customerId + ", subtotal: " + subtotal + ")");

        // Gọi API getApplicableVouchers với subtotal để backend lọc
        api.getApplicableVouchers(customerId, subtotal).enqueue(new Callback<VoucherListResponse>() {
            @Override
            public void onResponse(Call<VoucherListResponse> call, Response<VoucherListResponse> response) {
                Log.d(TAG, "API Response Code: " + response.code());

                if (response.isSuccessful() && response.body() != null) {
                    Log.d(TAG, "API isSuccess status: " + response.body().isSuccess());

                    if (response.body().isSuccess()) {
                        availableVouchers.clear();
                        List<VoucherDto> fetchedVouchers = response.body().getVouchers();
                        availableVouchers.addAll(fetchedVouchers);

                        // ⭐ LOGIC TỰ ĐỘNG CHỌN SAU KHI REDEEM
                        int newSelectedPosition = RecyclerView.NO_POSITION;
                        if (lastRedeemedCode != null) {
                            for (int i = 0; i < availableVouchers.size(); i++) {
                                if (availableVouchers.get(i).getVoucherCode().equalsIgnoreCase(lastRedeemedCode)) {
                                    newSelectedPosition = i;
                                    break;
                                }
                            }
                        }

                        // Cập nhật adapter và trạng thái chọn
                        adapter.notifyDataSetChanged();
                        if (newSelectedPosition != RecyclerView.NO_POSITION) {
                            // Tự động chọn voucher vừa redeem
                            adapter.setSelectedPosition(newSelectedPosition);
                            // ⭐ MỚI (Đã sửa): Sử dụng phương thức nội bộ của adapter
                            if (newSelectedPosition != RecyclerView.NO_POSITION) {
                                // Tự động chọn voucher vừa redeem
                                adapter.setSelectedPosition(newSelectedPosition); // Vẫn dùng setSelectedPosition để đặt trạng thái UI
                                // ⭐ Thao tác này sẽ kích hoạt listener
                                adapter.selectVoucherAndNotify(newSelectedPosition);
                            }
                        }

                        // Xóa mã redeem sau khi xử lý
                        lastRedeemedCode = null;


                        Log.d(TAG, "Successfully fetched " + availableVouchers.size() + " applicable vouchers.");

                        if (availableVouchers.isEmpty()) {
                            Toast.makeText(VoucherSelectionActivity.this, "Không có voucher nào khả dụng cho đơn hàng này.", Toast.LENGTH_LONG).show();
                        } else {
                            Toast.makeText(VoucherSelectionActivity.this, "Tìm thấy " + availableVouchers.size() + " voucher phù hợp.", Toast.LENGTH_SHORT).show();
                        }

                    } else {
                        String rawMessage = response.body().getMessage();
                        String safeMessage = (rawMessage != null && !rawMessage.isEmpty())
                                ? rawMessage
                                : "Lỗi không xác định khi tải voucher từ máy chủ. (isSuccess=false)";

                        Log.e(TAG, "Server returned error (isSuccess=false). Message: " + safeMessage);
                        Toast.makeText(VoucherSelectionActivity.this, safeMessage, Toast.LENGTH_LONG).show();
                    }
                } else {
                    String errorBody = "";
                    try {
                        if (response.errorBody() != null) {
                            errorBody = response.errorBody().string();
                        }
                    } catch (Exception e) {
                        errorBody = "Error body could not be read.";
                    }
                    Log.e(TAG, "API Failed with HTTP Code " + response.code() + ". Error Body: " + errorBody);
                    Toast.makeText(VoucherSelectionActivity.this, "Lỗi phản hồi API (Code " + response.code() + ").", Toast.LENGTH_LONG).show();
                }
            }

            @Override
            public void onFailure(Call<VoucherListResponse> call, Throwable t) {
                Log.e(TAG, "Network failure/Exception occurred: " + t.getMessage(), t);
                Toast.makeText(VoucherSelectionActivity.this, "Lỗi kết nối khi tải voucher: " + t.getMessage(), Toast.LENGTH_LONG).show();
            }
        });
    }

    private void applyVoucher() {
        Intent resultIntent = new Intent();

        // Nếu selectedVoucher là null, nó có nghĩa là người dùng chọn "Không dùng Voucher"
        resultIntent.putExtra("SELECTED_VOUCHER", selectedVoucher);
        setResult(RESULT_OK, resultIntent);
        finish();
    }

    private int getCurrentCustomerId() {
        SharedPreferences prefs = getSharedPreferences("auth", MODE_PRIVATE);
        int customerId = prefs.getInt("customerID", -1);
        Log.d(TAG, "SharedPreferences read customerID: " + customerId);
        return customerId;
    }
}