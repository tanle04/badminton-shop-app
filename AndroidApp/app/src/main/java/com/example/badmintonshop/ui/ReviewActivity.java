package com.example.badmintonshop.ui;

import android.os.Bundle;
import android.view.MenuItem;
import android.widget.Toast;

import androidx.annotation.NonNull;
import androidx.appcompat.app.AppCompatActivity;
import androidx.recyclerview.widget.LinearLayoutManager;
import androidx.recyclerview.widget.RecyclerView;

import com.example.badmintonshop.R;
import com.example.badmintonshop.adapter.ReviewAdapter;
import com.example.badmintonshop.network.ApiClient;
import com.google.android.material.button.MaterialButton;
import com.google.android.material.appbar.MaterialToolbar;

import java.util.ArrayList;
import java.util.List;

public class ReviewActivity extends AppCompatActivity {

    private RecyclerView recyclerView;
    private MaterialButton btnSubmitAllReviews;
    private int orderId;

    // ⭐ LƯU Ý: Bạn cần tạo ReviewAdapter và ReviewItemModel
    // Lớp ReviewItemModel sẽ chứa dữ liệu OrderDetailDto + các trường mới (rating, content)

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_review);

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

    private void setupRecyclerView() {
        recyclerView.setLayoutManager(new LinearLayoutManager(this));
        // ⭐ Chú ý: Bạn cần tạo ReviewAdapter và truyền danh sách các sản phẩm cần review
        // adapter = new ReviewAdapter(this, reviewItemsList);
        // recyclerView.setAdapter(adapter);
    }

    private void loadOrderDetailsForReview(int orderId) {
        // ⭐ Bước tiếp theo: Gọi API để lấy chi tiết các sản phẩm trong đơn hàng này
        // (Bạn cần một API orders/get_order_details.php mới hoặc tái sử dụng API cũ)

        Toast.makeText(this, "Đang tải chi tiết đơn hàng " + orderId, Toast.LENGTH_SHORT).show();

        // Ví dụ: Sau khi tải data, cập nhật adapter:
        // List<ReviewItemModel> reviewItems = convertOrderDetailsToReviewItems(data);
        // adapter.updateData(reviewItems);
    }

    private void submitReviews() {
        // ⭐ Bước cuối cùng: Lấy dữ liệu từ adapter và gửi lên API reviews/submit.php
        Toast.makeText(this, "Đang gửi đánh giá...", Toast.LENGTH_LONG).show();
    }
}