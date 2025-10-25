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
import androidx.annotation.NonNull;
import androidx.recyclerview.widget.LinearLayoutManager;
import androidx.recyclerview.widget.RecyclerView;
import com.bumptech.glide.Glide;
import com.example.badmintonshop.R;
import com.example.badmintonshop.model.ReviewItemModel;
import com.example.badmintonshop.network.dto.OrderDetailDto;
import com.example.badmintonshop.adapter.ReviewMediaAdapter.MediaDeleteListener;

import java.util.ArrayList; // Thêm import cho ArrayList
import java.util.List;

public class ReviewAdapter extends RecyclerView.Adapter<ReviewAdapter.ReviewViewHolder> {

    private final Context context;
    private List<ReviewItemModel> reviewItems;
    private final ReviewAdapterListener listener;
    private static final String BASE_IMAGE_URL = "http://10.0.2.2/api/BadmintonShop/images/";

    // 1. INTERFACE CHO SỰ KIỆN CLICK (Giao tiếp với Activity)
    public interface ReviewAdapterListener {
        void onPhotoClicked(int position);
        void onVideoClicked(int position);
        // BỔ SUNG: Listener để Activity xử lý việc xóa media khỏi Model chính
        void onMediaDeleted(int reviewPosition, int mediaPosition);
    }

    // CONSTRUCTOR MỚI: Nhận thêm Listener
    public ReviewAdapter(Context context, List<ReviewItemModel> reviewItems, ReviewAdapterListener listener) {
        this.context = context;
        this.reviewItems = reviewItems;
        this.listener = listener;
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
        String variantInfo = detail.getVariantDetails() != null ? " (" + detail.getVariantDetails() + ")" : "";
        holder.tvProductName.setText(String.format("%s%s x%d", detail.getProductName(), variantInfo, detail.getQuantity()));

        Glide.with(context)
                .load(BASE_IMAGE_URL + detail.getImageUrl())
                .placeholder(R.drawable.ic_badminton_logo)
                .error(R.drawable.ic_badminton_logo)
                .into(holder.imgProduct);

        // 2. Gán giá trị đánh giá hiện tại
        holder.ratingBar.setRating(model.getRating());
        holder.etContent.setText(model.getReviewContent());

        // ⭐ 3. LOGIC HIỂN THỊ MEDIA PREVIEW (Đã sửa để xử lý List<Uri> cho Video)

        List<android.net.Uri> mediaList = new ArrayList<>();

        // Thêm Ảnh (PhotoUris đã là List)
        if (model.getPhotoUris() != null && !model.getPhotoUris().isEmpty()) {
            mediaList.addAll(model.getPhotoUris());
        }

        // Thêm Video (VideoUris đã là List)
        if (model.getVideoUris() != null && !model.getVideoUris().isEmpty()) {
            mediaList.addAll(model.getVideoUris());
        }

        if (!mediaList.isEmpty()) {
            holder.recyclerMediaPreview.setVisibility(View.VISIBLE);

            // ⭐ Xử lý isVideoMode: Cần truyền logic cho Adapter con để nhận diện video
            // Chúng ta không thể chỉ dùng isVideoMode boolean, cần truyền List tổng hợp.
            // Để đơn giản, ta sẽ đặt logic kiểm tra video trong ReviewMediaAdapter.java.

            // ⭐ Khởi tạo Media Adapter con và truyền Listener xóa
            // Chúng ta truyền List Uri tổng hợp của cả ảnh và video
            ReviewMediaAdapter mediaAdapter = new ReviewMediaAdapter(context, mediaList, false, // isVideoSelected không còn cần thiết
                    // Triển khai MediaDeleteListener
                    new MediaDeleteListener() {
                        @Override
                        public void onMediaDeleted(int mediaPosition) {
                            // Gọi lại Activity thông qua Listener chính của Adapter
                            int currentReviewPos = holder.getAdapterPosition();
                            if (currentReviewPos != RecyclerView.NO_POSITION && listener != null) {
                                // Xử lý logic xóa trong Activity (Cần biết là ảnh hay video để xóa đúng)
                                listener.onMediaDeleted(currentReviewPos, mediaPosition);
                            }
                        }
                    }
            );

            // Chỉ set LayoutManager 1 lần
            if (holder.recyclerMediaPreview.getLayoutManager() == null) {
                holder.recyclerMediaPreview.setLayoutManager(new LinearLayoutManager(context, LinearLayoutManager.HORIZONTAL, false));
            }
            holder.recyclerMediaPreview.setAdapter(mediaAdapter);

        } else {
            holder.recyclerMediaPreview.setVisibility(View.GONE);
            holder.recyclerMediaPreview.setAdapter(null); // Giải phóng Adapter
        }

        // 4. Xử lý sự kiện nhập liệu (Giữ nguyên - đã an toàn)
        holder.ratingBar.setOnRatingBarChangeListener((ratingBar, rating, fromUser) -> {
            if (fromUser) {
                model.setRating((int) rating);
            }
        });

        if (holder.etContent.getTag() instanceof TextWatcher) {
            holder.etContent.removeTextChangedListener((TextWatcher) holder.etContent.getTag());
        }

        TextWatcher watcher = new TextWatcher() {
            @Override public void beforeTextChanged(CharSequence s, int start, int count, int after) {}
            @Override public void onTextChanged(CharSequence s, int start, int before, int count) {}
            @Override
            public void afterTextChanged(Editable s) {
                model.setReviewContent(s.toString());
            }
        };
        holder.etContent.addTextChangedListener(watcher);
        holder.etContent.setTag(watcher);

        // 5. XỬ LÝ CLICK CHO NÚT ẢNH VÀ VIDEO (Dùng getAdapterPosition() an toàn)
        if (listener != null) {
            holder.btnPhoto.setOnClickListener(v -> {
                int currentPos = holder.getAdapterPosition();
                if (currentPos != RecyclerView.NO_POSITION) {
                    listener.onPhotoClicked(currentPos);
                }
            });

            holder.btnVideo.setOnClickListener(v -> {
                int currentPos = holder.getAdapterPosition();
                if (currentPos != RecyclerView.NO_POSITION) {
                    listener.onVideoClicked(currentPos);
                }
            });
        }
    }

    @Override
    public int getItemCount() {
        return reviewItems != null ? reviewItems.size() : 0;
    }

    public List<ReviewItemModel> getReviewItems() {
        return reviewItems;
    }

    public void updateData(List<ReviewItemModel> newItems) {
        this.reviewItems = newItems;
        notifyDataSetChanged();
    }

    // --- VIEWHOLDER CLASS ---

    public static class ReviewViewHolder extends RecyclerView.ViewHolder {
        ImageView imgProduct;
        TextView tvProductName;
        RatingBar ratingBar;
        EditText etContent;
        TextView btnPhoto;
        TextView btnVideo;
        RecyclerView recyclerMediaPreview;

        public ReviewViewHolder(@NonNull View itemView) {
            super(itemView);
            imgProduct = itemView.findViewById(R.id.img_product_review);
            tvProductName = itemView.findViewById(R.id.tv_product_name_review);
            ratingBar = itemView.findViewById(R.id.rating_bar_product);
            etContent = itemView.findViewById(R.id.et_review_content);
            btnPhoto = itemView.findViewById(R.id.btn_photo);
            btnVideo = itemView.findViewById(R.id.btn_video);
            recyclerMediaPreview = itemView.findViewById(R.id.recycler_media_preview);
        }
    }
}