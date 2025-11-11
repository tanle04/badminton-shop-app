package com.example.badmintonshop.ui;

import android.os.Bundle;
import android.widget.ImageButton;
import android.widget.ImageView;
import android.widget.Toast;

import androidx.appcompat.app.AppCompatActivity;

import com.bumptech.glide.Glide;
import com.example.badmintonshop.R;

public class FullScreenImageActivity extends AppCompatActivity {

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_full_screen_image);

        ImageView imgFullScreen = findViewById(R.id.imgFullScreen);
        ImageButton btnClose = findViewById(R.id.btnClose);

        if (getSupportActionBar() != null) {
            getSupportActionBar().hide();
        }

        // 1. Nhận URL từ Intent
        String imageUrl = getIntent().getStringExtra("IMAGE_URL");

        if (imageUrl != null && !imageUrl.isEmpty()) {
            // 2. Tải và hiển thị ảnh bằng Glide
            Glide.with(this)
                    .load(imageUrl)
                    .error(R.drawable.ic_badminton_logo)
                    .into(imgFullScreen);
        } else {
            Toast.makeText(this, "Không tìm thấy URL ảnh.", Toast.LENGTH_SHORT).show();
            finish();
        }

        // 3. Xử lý đóng Activity
        btnClose.setOnClickListener(v -> finish());
        imgFullScreen.setOnClickListener(v -> finish()); // Đóng khi chạm vào ảnh
    }
}