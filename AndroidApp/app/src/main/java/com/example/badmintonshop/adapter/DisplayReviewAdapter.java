package com.example.badmintonshop.adapter;

import android.content.Context;
import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.widget.RatingBar;
import android.widget.TextView;
import androidx.annotation.NonNull;
import androidx.recyclerview.widget.LinearLayoutManager;
import androidx.recyclerview.widget.RecyclerView;
import com.example.badmintonshop.R;
import com.example.badmintonshop.network.dto.ReviewDto;

import java.util.List;

// ⭐ THÊM IMPORT THIẾU
import com.example.badmintonshop.adapter.DisplayReviewMediaAdapter;

public class DisplayReviewAdapter extends RecyclerView.Adapter<DisplayReviewAdapter.ReviewViewHolder> {

    private final Context context;
    private final List<ReviewDto> reviews;

    // Interface để xử lý sự kiện click vào ảnh đính kèm (mở preview)
    public interface ReviewMediaClickListener {
        void onMediaClick(String mediaUrl);
    }
    private final ReviewMediaClickListener mediaClickListener;

    // CONSTRUCTOR
    public DisplayReviewAdapter(Context context, List<ReviewDto> reviews, ReviewMediaClickListener mediaClickListener) {
        this.context = context;
        this.reviews = reviews;
        this.mediaClickListener = mediaClickListener;
    }

    @NonNull
    @Override
    public ReviewViewHolder onCreateViewHolder(@NonNull ViewGroup parent, int viewType) {
        // Sử dụng layout item_review.xml
        View view = LayoutInflater.from(context).inflate(R.layout.item_review, parent, false);
        return new ReviewViewHolder(view);
    }

    @Override
    public void onBindViewHolder(@NonNull ReviewViewHolder holder, int position) {
        ReviewDto review = reviews.get(position);

        // 1. Gán dữ liệu cơ bản
        holder.tvCustomerName.setText(review.getCustomerName());
        holder.tvReviewDate.setText(review.getReviewDate());

        // 2. Rating và Nội dung
        holder.ratingBarReviewItem.setRating((float) review.getRating());
        holder.tvReviewContent.setText(review.getReviewContent());

        // 3. Media đính kèm (Hiển thị)
        List<String> mediaUrls = review.getReviewPhotos();
        if (mediaUrls != null && !mediaUrls.isEmpty()) {
            holder.recyclerReviewMedia.setVisibility(View.VISIBLE);

            // Sử dụng DisplayReviewMediaAdapter
            DisplayReviewMediaAdapter mediaAdapter = new DisplayReviewMediaAdapter(context, mediaUrls, mediaClickListener);

            if (holder.recyclerReviewMedia.getLayoutManager() == null) {
                holder.recyclerReviewMedia.setLayoutManager(new LinearLayoutManager(context, LinearLayoutManager.HORIZONTAL, false));
            }
            holder.recyclerReviewMedia.setAdapter(mediaAdapter);
        } else {
            holder.recyclerReviewMedia.setVisibility(View.GONE);
        }
    }

    @Override
    public int getItemCount() {
        return reviews != null ? reviews.size() : 0;
    }

    // --- VIEWHOLDER CLASS ---

    public static class ReviewViewHolder extends RecyclerView.ViewHolder {
        TextView tvCustomerName;
        TextView tvReviewDate;
        RatingBar ratingBarReviewItem;
        TextView tvReviewContent;
        RecyclerView recyclerReviewMedia;

        public ReviewViewHolder(@NonNull View itemView) {
            super(itemView);
            tvCustomerName = itemView.findViewById(R.id.tvCustomerName);
            tvReviewDate = itemView.findViewById(R.id.tvReviewDate);
            ratingBarReviewItem = itemView.findViewById(R.id.ratingBarReviewItem);
            tvReviewContent = itemView.findViewById(R.id.tvReviewContent);
            recyclerReviewMedia = itemView.findViewById(R.id.recyclerReviewMedia);
        }
    }
}