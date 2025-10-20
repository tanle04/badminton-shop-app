package com.example.badmintonshop.ui;

import android.app.AlertDialog;
import android.content.Intent;
import android.content.SharedPreferences;
import android.os.Bundle;
import android.util.Log; // Thêm Log để debug
import android.view.View;
import android.widget.TextView;
import android.widget.Toast;

import androidx.annotation.Nullable;
import androidx.appcompat.app.AppCompatActivity;
import androidx.recyclerview.widget.LinearLayoutManager;
import androidx.recyclerview.widget.RecyclerView;

import com.example.badmintonshop.R;
import com.example.badmintonshop.adapter.AddressAdapter;
import com.example.badmintonshop.network.ApiClient;
import com.example.badmintonshop.network.ApiService;
import com.example.badmintonshop.network.dto.AddressDto;
import com.example.badmintonshop.network.dto.AddressListResponse;
import com.example.badmintonshop.network.dto.ApiResponse;
import com.google.android.material.appbar.MaterialToolbar;
import com.google.android.material.button.MaterialButton;

import java.util.ArrayList;
import java.util.List;

import retrofit2.Call;
import retrofit2.Callback;
import retrofit2.Response;

public class AddressActivity extends AppCompatActivity implements AddressAdapter.AddressAdapterListener {

    private static final String TAG = "AddressActivity";
    private static final int ADD_EDIT_ADDRESS_REQUEST_CODE = 101;

    private RecyclerView recyclerView;
    private AddressAdapter addressAdapter;
    private ApiService api;
    private TextView tvEmptyAddress;
    private MaterialButton btnAddNewAddress;
    private MaterialToolbar toolbar;

    private List<AddressDto> addressList = new ArrayList<>();
    private boolean isForSelection = false; // Biến để xác định chế độ chọn

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_address);

        toolbar = findViewById(R.id.toolbar);
        recyclerView = findViewById(R.id.recycler_addresses);
        tvEmptyAddress = findViewById(R.id.tv_empty_address);
        btnAddNewAddress = findViewById(R.id.btn_add_new_address);
        api = ApiClient.getApiService();

        // Lấy chế độ: Quản lý (mặc định) hay Chọn
        if (getIntent().hasExtra("IS_FOR_SELECTION")) {
            isForSelection = getIntent().getBooleanExtra("IS_FOR_SELECTION", false);
            toolbar.setTitle(isForSelection ? "Chọn địa chỉ giao hàng" : "Quản lý địa chỉ");
        } else {
            toolbar.setTitle("Quản lý địa chỉ");
        }

        setSupportActionBar(toolbar);
        toolbar.setNavigationOnClickListener(v -> finish());

        setupRecyclerView(); // Gọi hàm setup sau khi xác định isForSelection

        btnAddNewAddress.setOnClickListener(v -> {
            Intent intent = new Intent(AddressActivity.this, AddEditAddressActivity.class);
            startActivityForResult(intent, ADD_EDIT_ADDRESS_REQUEST_CODE);
        });
    }

    @Override
    protected void onResume() {
        super.onResume();
        fetchAddresses();
    }

    private int getCurrentCustomerId() {
        SharedPreferences prefs = getSharedPreferences("auth", MODE_PRIVATE);
        // Trả về -1 nếu không tìm thấy customerID, cần xử lý trong fetchAddresses
        return prefs.getInt("customerID", -1);
    }

    // ⭐ SỬA ĐỔI QUAN TRỌNG: Truyền isForSelection vào AddressAdapter
    private void setupRecyclerView() {
        recyclerView.setLayoutManager(new LinearLayoutManager(this));
        // Truyền AddressAdapterListener (chính là Activity này) và chế độ isForSelection
        addressAdapter = new AddressAdapter(this, addressList, this);
        recyclerView.setAdapter(addressAdapter);
    }

    private void fetchAddresses() {
        int customerId = getCurrentCustomerId();
        if (customerId == -1) {
            Toast.makeText(this, "Vui lòng đăng nhập để xem địa chỉ.", Toast.LENGTH_SHORT).show();
            return;
        }

        api.getAddresses(customerId).enqueue(new Callback<AddressListResponse>() {
            @Override
            public void onResponse(Call<AddressListResponse> call, Response<AddressListResponse> response) {
                if (response.isSuccessful() && response.body() != null) {
                    if (response.body().isSuccess()) {
                        addressList = response.body().getAddresses();
                        addressAdapter.updateData(addressList);
                        // Cập nhật trạng thái View
                        boolean isEmpty = addressList == null || addressList.isEmpty();
                        tvEmptyAddress.setVisibility(isEmpty ? View.VISIBLE : View.GONE);
                        recyclerView.setVisibility(isEmpty ? View.GONE : View.VISIBLE);
                    } else {
                        Log.e(TAG, "Fetch Failed: " + response.body().getMessage());
                        Toast.makeText(AddressActivity.this, "Lỗi tải địa chỉ: " + response.body().getMessage(), Toast.LENGTH_SHORT).show();
                    }
                } else {
                    Log.e(TAG, "HTTP Error: " + response.code());
                    Toast.makeText(AddressActivity.this, "Không tải được địa chỉ (Lỗi HTTP " + response.code() + ")", Toast.LENGTH_SHORT).show();
                }
            }
            @Override
            public void onFailure(Call<AddressListResponse> call, Throwable t) {
                Log.e(TAG, "Connection Error: ", t);
                Toast.makeText(AddressActivity.this, "Lỗi kết nối mạng.", Toast.LENGTH_SHORT).show();
            }
        });
    }

    // --- Triển khai các phương thức từ AddressAdapterListener ---

    @Override
    public void onEditClicked(AddressDto address) {
        Intent intent = new Intent(this, AddEditAddressActivity.class);
        intent.putExtra("EDIT_ADDRESS", address);
        startActivityForResult(intent, ADD_EDIT_ADDRESS_REQUEST_CODE);
    }

    @Override
    public void onDeleteClicked(AddressDto address) {
        new AlertDialog.Builder(this)
                .setTitle("Xóa địa chỉ")
                .setMessage("Bạn có chắc muốn xóa địa chỉ này?")
                .setPositiveButton("Xóa", (dialog, which) -> deleteAddress(address))
                .setNegativeButton("Hủy", null)
                .show();
    }

    private void deleteAddress(AddressDto address) {
        api.deleteAddress(address.getAddressID(), getCurrentCustomerId()).enqueue(new Callback<ApiResponse>() {
            @Override
            public void onResponse(Call<ApiResponse> call, Response<ApiResponse> response) {
                if(response.isSuccessful() && response.body() != null && response.body().isSuccess()) {
                    Toast.makeText(AddressActivity.this, "Địa chỉ đã được xóa.", Toast.LENGTH_SHORT).show();
                    fetchAddresses(); // Tải lại danh sách sau khi xóa thành công
                } else {
                    String message = response.body() != null ? response.body().getMessage() : "Lỗi server.";
                    Toast.makeText(AddressActivity.this, "Xóa thất bại: " + message, Toast.LENGTH_LONG).show();
                    Log.e(TAG, "Delete Failed: " + (response.body() != null ? response.body().getMessage() : "HTTP " + response.code()));
                }
            }
            @Override
            public void onFailure(Call<ApiResponse> call, Throwable t) {
                Log.e(TAG, "Delete Connection Error: ", t);
                Toast.makeText(AddressActivity.this, "Lỗi kết nối khi xóa.", Toast.LENGTH_SHORT).show();
            }
        });
    }

    @Override
    public void onSetDefaultClicked(AddressDto address) {
        api.setDefaultAddress(address.getAddressID(), getCurrentCustomerId()).enqueue(new Callback<ApiResponse>() {
            @Override
            public void onResponse(Call<ApiResponse> call, Response<ApiResponse> response) {
                if (response.isSuccessful() && response.body() != null && response.body().isSuccess()) {
                    Toast.makeText(AddressActivity.this, "Địa chỉ đã được đặt làm mặc định.", Toast.LENGTH_SHORT).show();
                    fetchAddresses(); // Tải lại để cập nhật hiển thị
                } else {
                    String message = response.body() != null ? response.body().getMessage() : "Lỗi server.";
                    Toast.makeText(AddressActivity.this, "Thao tác thất bại: " + message, Toast.LENGTH_LONG).show();
                }
            }
            @Override
            public void onFailure(Call<ApiResponse> call, Throwable t) {
                Toast.makeText(AddressActivity.this, "Lỗi kết nối khi đặt mặc định.", Toast.LENGTH_SHORT).show();
            }
        });
    }

    // Triển khai hàm chọn địa chỉ (chỉ gọi khi isForSelection = true)
    @Override
    public void onAddressSelected(AddressDto address) {
        if (isForSelection) {
            Intent resultIntent = new Intent();
            resultIntent.putExtra("SELECTED_ADDRESS", address);
            setResult(RESULT_OK, resultIntent);
            finish();
        }
    }

    @Override
    protected void onActivityResult(int requestCode, int resultCode, @Nullable Intent data) {
        super.onActivityResult(requestCode, resultCode, data);
        // Nếu thêm/sửa địa chỉ thành công, tải lại danh sách
        if (requestCode == ADD_EDIT_ADDRESS_REQUEST_CODE && resultCode == RESULT_OK) {
            fetchAddresses();
        }
    }
}