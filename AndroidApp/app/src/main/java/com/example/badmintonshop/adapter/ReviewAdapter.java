package com.example.badmintonshop.adapter;

import android.content.Context;
import android.text.Editable;
import android.text.TextWatcher;
import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.widget.EditText;
import android.widget.ImageView;
import android.widget.RatingBar;
import android.widget.TextView;
import android.widget.Toast;

import androidx.annotation.NonNull;
import androidx.recyclerview.widget.RecyclerView;

import com.bumptech.glide.Glide;
import com.example.badmintonshop.R;
import com.example.badmintonshop.model.ReviewItemModel;
import com.example.badmintonshop.network.dto.OrderDetailDto;

import java.util.List;

public class ReviewAdapter extends RecyclerView.Adapter<ReviewAdapter.ReviewViewHolder> {

    private final Context context;
    private List<ReviewItemModel> reviewItems;
    private static final String BASE_IMAGE_URL = "http://10.0.2.2/api/BadmintonShop/images/uploads/";

    public ReviewAdapter(Context context, List<ReviewItemModel> reviewItems) {
        this.context = context;
        this.reviewItems = reviewItems;
    }

    @NonNull
    @Override
    public ReviewViewHolder onCreateViewHolder(@NonNull ViewGroup parent, int viewType) {
        View view = LayoutInflater.from(context).inflate(R.layout.item_single_review, parent, false);
        return new ReviewViewHolder(view);
    }

    @Override
    public void onBindViewHolder(@NonNull ReviewViewHolder holder, int position) {
        ReviewItemModel model = reviewItems.get(position);
        OrderDetailDto detail = model.getOrderDetail();

        // 1. Gán dữ liệu sản phẩm
        holder.tvProductName.setText(detail.getProductName());
        Glide.with(context)
                .load(BASE_IMAGE_URL + detail.getImageUrl())
                .placeholder(R.drawable.ic_badminton_logo)
                .error(R.drawable.ic_badminton_logo)
                .into(holder.imgProduct);

        // 2. Gán giá trị đánh giá hiện tại
        holder.ratingBar.setRating(model.getRating());
        holder.etContent.setText(model.getReviewContent());

        // 3. Xử lý sự kiện nhập liệu

        // Cập nhật Rating
        holder.ratingBar.setOnRatingBarChangeListener((ratingBar, rating, fromUser) -> {
            if (fromUser) {
                model.setRating((int) rating);
            }
        });

        // Cập nhật Nội dung
        holder.etContent.removeTextChangedListener((TextWatcher) holder.etContent.getTag());

        TextWatcher watcher = new TextWatcher() {
            @Override
            public void beforeTextChanged(CharSequence s, int start, int count, int after) {}
            @Override
            public void onTextChanged(CharSequence s, int start, int before, int count) {}

            @Override
            public void afterTextChanged(Editable s) {
                model.setReviewContent(s.toString());
            }
        };
        holder.etContent.addTextChangedListener(watcher);
        holder.etContent.setTag(watcher); // Lưu watcher vào tag để tránh double listener
    }

    @Override
    public int getItemCount() {
        return reviewItems != null ? reviewItems.size() : 0;
    }

    // Getter để Activity lấy danh sách đánh giá hoàn chỉnh
    public List<ReviewItemModel> getReviewItems() {
        return reviewItems;
    }

    // --- VIEWHOLDER CLASS ---

    public static class ReviewViewHolder extends RecyclerView.ViewHolder {
        ImageView imgProduct;
        TextView tvProductName;
        RatingBar ratingBar;
        EditText etContent;
        // Button photo/video...

        public ReviewViewHolder(@NonNull View itemView) {
            super(itemView);
            imgProduct = itemView.findViewById(R.id.img_product_review);
            tvProductName = itemView.findViewById(R.id.tv_product_name_review);
            ratingBar = itemView.findViewById(R.id.rating_bar_product);
            etContent = itemView.findViewById(R.id.et_review_content);
        }
    }
}