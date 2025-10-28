package com.example.badmintonshop.ui;

import android.content.Intent;
import android.os.Bundle;
import android.util.Log;
import android.view.View;
import android.widget.TextView;
import android.widget.Toast;

import androidx.annotation.NonNull;
import androidx.appcompat.app.AppCompatActivity;
import androidx.recyclerview.widget.LinearLayoutManager;
import androidx.recyclerview.widget.RecyclerView;

import com.example.badmintonshop.R;
import com.example.badmintonshop.adapter.ShippingRateAdapter;
import com.example.badmintonshop.network.ApiClient;
import com.example.badmintonshop.network.ApiService;
import com.example.badmintonshop.network.dto.ShippingRateDto;
import com.example.badmintonshop.network.dto.ShippingRatesResponse;
import com.google.android.material.appbar.MaterialToolbar;
import com.google.android.material.button.MaterialButton;

import java.util.ArrayList;
import java.util.List;

import retrofit2.Call;
import retrofit2.Callback;
import retrofit2.Response;

public class ShippingSelectionActivity extends AppCompatActivity implements ShippingRateAdapter.OnRateSelectedListener {

    private static final String TAG = "SHIPPING_SELECTION";
    private ApiService api;
    private RecyclerView recyclerView;
    private TextView tvLoadingError;
    private MaterialButton btnConfirm;
    private ShippingRateAdapter adapter;
    private List<ShippingRateDto> rateList = new ArrayList<>();

    private ShippingRateDto selectedRate = null;

    // ⭐ SỬA LỖI: Không dùng subtotal, dùng itemsJson
    // private double subtotal = 0.0;
    private String itemsJson;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_shipping_selection); // Cần tạo layout này

        api = ApiClient.getApiService();

        // ⭐ SỬA LỖI: Lấy ITEMS_JSON thay vì SUBTOTAL
        // subtotal = getIntent().getDoubleExtra("SUBTOTAL", 0.0);
        itemsJson = getIntent().getStringExtra("ITEMS_JSON");

        // Thêm kiểm tra
        if (itemsJson == null || itemsJson.isEmpty()) {
            Toast.makeText(this, "Lỗi: Không có sản phẩm để tính phí.", Toast.LENGTH_SHORT).show();
            Log.e(TAG, "itemsJSON is null or empty. Cannot fetch rates.");
            finish();
            return;
        }

        MaterialToolbar toolbar = findViewById(R.id.toolbar);
        toolbar.setNavigationOnClickListener(v -> finish());

        recyclerView = findViewById(R.id.recycler_shipping_rates); // Cần tạo ID này
        tvLoadingError = findViewById(R.id.tv_loading_error); // Cần tạo ID này
        btnConfirm = findViewById(R.id.btn_confirm_selection); // Cần tạo ID này

        setupRecyclerView();
        fetchShippingRates();

        btnConfirm.setOnClickListener(v -> {
            if (selectedRate != null) {
                Intent resultIntent = new Intent();
                resultIntent.putExtra("SELECTED_SHIPPING_RATE", selectedRate);
                setResult(RESULT_OK, resultIntent);
                finish();
            } else {
                Toast.makeText(this, "Vui lòng chọn một phương thức vận chuyển.", Toast.LENGTH_SHORT).show();
            }
        });
    }

    private void setupRecyclerView() {
        recyclerView.setLayoutManager(new LinearLayoutManager(this));
        adapter = new ShippingRateAdapter(this, rateList, this);
        recyclerView.setAdapter(adapter);
    }

    private void fetchShippingRates() {
        tvLoadingError.setVisibility(View.GONE);

        // ⭐ SỬA LỖI: Gọi API get_rates.php với itemsJson, không dùng subtotal
        api.getShippingRates(itemsJson, 0) // Giả định addressID = 0
                .enqueue(new Callback<ShippingRatesResponse>() {
                    @Override
                    public void onResponse(@NonNull Call<ShippingRatesResponse> call, @NonNull Response<ShippingRatesResponse> response) {
                        if (response.isSuccessful() && response.body() != null && response.body().isSuccess()) {
                            List<ShippingRateDto> fetchedRates = response.body().getData();
                            if (fetchedRates != null && !fetchedRates.isEmpty()) {
                                rateList.clear();
                                rateList.addAll(fetchedRates);
                                adapter.notifyDataSetChanged();

                                // Logic Tự động chọn Rate đầu tiên (Giữ nguyên, logic này tốt)
                                selectedRate = fetchedRates.get(0);
                                adapter.setSelectedPosition(0);
                                onRateSelected(selectedRate);
                                btnConfirm.setEnabled(true);
                            } else {
                                tvLoadingError.setText("Không có phương thức vận chuyển khả dụng.");
                                tvLoadingError.setVisibility(View.VISIBLE);
                                btnConfirm.setEnabled(false);
                            }
                        } else {
                            String message = (response.body() != null && response.body().getMessage() != null)
                                    ? response.body().getMessage()
                                    : "Lỗi tải dữ liệu phí ship.";
                            tvLoadingError.setText(message);
                            tvLoadingError.setVisibility(View.VISIBLE);
                            btnConfirm.setEnabled(false);
                            Log.e(TAG, "API call failed: " + message);
                        }
                    }

                    @Override
                    public void onFailure(@NonNull Call<ShippingRatesResponse> call, @NonNull Throwable t) {
                        tvLoadingError.setText("Lỗi kết nối mạng: " + t.getMessage());
                        tvLoadingError.setVisibility(View.VISIBLE);
                        btnConfirm.setEnabled(false);
                        Log.e(TAG, "Network failed: " + t.getMessage());
                    }
                });
    }

    @Override
    public void onRateSelected(ShippingRateDto rate) {
        this.selectedRate = rate;
        // Bật nút xác nhận ngay sau khi chọn
        btnConfirm.setEnabled(rate != null);
    }
}