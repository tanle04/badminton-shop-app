package com.example.badmintonshop.ui;

import android.os.Bundle;
import android.text.Editable;
import android.text.TextWatcher;
import android.widget.EditText;
import android.widget.Toast;

import androidx.appcompat.app.AppCompatActivity;
import androidx.recyclerview.widget.LinearLayoutManager;
import androidx.recyclerview.widget.RecyclerView;

import com.example.badmintonshop.R;
import com.example.badmintonshop.adapter.ProductAdapter;
import com.example.badmintonshop.network.ApiClient;
import com.example.badmintonshop.network.ApiService;
import com.example.badmintonshop.network.dto.ProductListResponse;

import retrofit2.Call;
import retrofit2.Callback;
import retrofit2.Response;

public class SearchActivity extends AppCompatActivity {

    private EditText edtSearch;
    private RecyclerView recyclerSearch;
    private ApiService api;
    private ProductAdapter adapter;

    private static final String BASE_URL = "http://10.0.2.2/api/BadmintonShop/";

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_search);

        edtSearch = findViewById(R.id.edtSearch);
        recyclerSearch = findViewById(R.id.recyclerSearch);
        recyclerSearch.setLayoutManager(new LinearLayoutManager(this));

        api = ApiClient.get(BASE_URL).create(ApiService.class);

        edtSearch.addTextChangedListener(new TextWatcher() {
            @Override public void beforeTextChanged(CharSequence s, int start, int count, int after) {}
            @Override public void afterTextChanged(Editable s) {}

            @Override
            public void onTextChanged(CharSequence s, int start, int before, int count) {
                String keyword = s.toString().trim();
                if (keyword.length() >= 2) searchProducts(keyword);
            }
        });
    }

    private void searchProducts(String keyword) {
        api.searchProducts(keyword).enqueue(new Callback<ProductListResponse>() {
            @Override
            public void onResponse(Call<ProductListResponse> call, Response<ProductListResponse> response) {
                if (!response.isSuccessful() || response.body() == null) {
                    Toast.makeText(SearchActivity.this, "Không tải được kết quả", Toast.LENGTH_SHORT).show();
                    return;
                }
                adapter = new ProductAdapter(SearchActivity.this, response.body().getItems());
                recyclerSearch.setAdapter(adapter);
            }

            @Override
            public void onFailure(Call<ProductListResponse> call, Throwable t) {
                Toast.makeText(SearchActivity.this, "Lỗi: " + t.getMessage(), Toast.LENGTH_SHORT).show();
            }
        });
    }
}
