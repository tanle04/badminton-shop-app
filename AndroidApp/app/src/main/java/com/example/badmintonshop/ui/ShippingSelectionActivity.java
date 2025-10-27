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
    private double subtotal = 0.0;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_shipping_selection); // Cần tạo layout này

        api = ApiClient.getApiService();
        subtotal = getIntent().getDoubleExtra("SUBTOTAL", 0.0);

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
        // Lời gọi API get_rates.php với subtotal
        api.getShippingRates(subtotal, 0) // Giả định addressID = 0 nếu không dùng phí theo vùng
                .enqueue(new Callback<ShippingRatesResponse>() {
                    @Override
                    public void onResponse(@NonNull Call<ShippingRatesResponse> call, @NonNull Response<ShippingRatesResponse> response) {
                        if (response.isSuccessful() && response.body() != null && response.body().isSuccess()) {
                            List<ShippingRateDto> fetchedRates = response.body().getData();
                            if (fetchedRates != null && !fetchedRates.isEmpty()) {
                                rateList.clear();
                                rateList.addAll(fetchedRates);
                                adapter.notifyDataSetChanged();

                                // ⭐ SỬA LỖI: Logic Tự động chọn Rate đầu tiên ⭐
                                selectedRate = fetchedRates.get(0);

                                // 1. Cập nhật Adapter UI (sử dụng phương thức setter mới)
                                adapter.setSelectedPosition(0);

                                // 2. Kích hoạt Listener để cập nhật biến selectedRate (tùy chọn, nhưng an toàn hơn)
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