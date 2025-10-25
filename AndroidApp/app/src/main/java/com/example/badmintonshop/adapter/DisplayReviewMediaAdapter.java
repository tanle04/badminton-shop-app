package com.example.badmintonshop.adapter;

import android.content.Context;
import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.widget.ImageView;
import androidx.annotation.NonNull;
import androidx.recyclerview.widget.RecyclerView;
import com.bumptech.glide.Glide;
import com.example.badmintonshop.R;
import com.example.badmintonshop.adapter.DisplayReviewAdapter.ReviewMediaClickListener; // Dùng interface từ Adapter cha

import java.util.List;

/**
 * Adapter hiển thị Media (Ảnh/Video) đã được lưu trên Server (sử dụng URL)
 * Dùng cho màn hình ReviewListActivity (chỉ hiển thị, không có nút xóa).
 */
public class DisplayReviewMediaAdapter extends RecyclerView.Adapter<DisplayReviewMediaAdapter.MediaViewHolder> {

    private final Context context;
    private final List<String> mediaUrls; // Sử dụng String (URL)
    private final ReviewMediaClickListener listener;
    // ⭐ BASE_IMAGE_URL cần phải trỏ đến thư mục 'uploads' của API gốc (đã sửa trong các bước trước)
    private static final String BASE_IMAGE_URL = "http://10.0.2.2/api/uploads/";

    // Constructor nhận 3 tham số: Context, List<String> URL, Listener
    public DisplayReviewMediaAdapter(Context context, List<String> mediaUrls, ReviewMediaClickListener listener) {
        this.context = context;
        this.mediaUrls = mediaUrls;
        this.listener = listener;
    }

    @NonNull
    @Override
    public MediaViewHolder onCreateViewHolder(@NonNull ViewGroup parent, int viewType) {
        // Sử dụng item_review_media.xml
        View view = LayoutInflater.from(context).inflate(R.layout.item_review_media, parent, false);
        return new MediaViewHolder(view);
    }

    @Override
    public void onBindViewHolder(@NonNull MediaViewHolder holder, int position) {
        String url = mediaUrls.get(position);

        String fullUrl = BASE_IMAGE_URL + url;

        // 1. Tải ảnh/thumbnail (Glide không thể tạo thumbnail video từ URL, nên chỉ tải ảnh)
        Glide.with(context)
                .load(fullUrl)
                .placeholder(R.drawable.ic_badminton_logo)
                .error(R.drawable.ic_badminton_logo)
                .centerCrop()
                .into(holder.imgReviewMedia);

        // 2. Quản lý Icon Play
        boolean isVideo = url.toLowerCase().endsWith(".mp4") || url.toLowerCase().endsWith(".mov");

        holder.imgPlayIcon.setVisibility(isVideo ? View.VISIBLE : View.GONE);
        holder.btnDelete.setVisibility(View.GONE); // LUÔN ẨN NÚT XÓA

        // 3. Xử lý click (Mở Dialog hoặc Activity preview)
        holder.itemView.setOnClickListener(v -> {
            if (listener != null) {
                listener.onMediaClick(url);
            }
        });
    }

    @Override
    public int getItemCount() {
        return mediaUrls != null ? mediaUrls.size() : 0;
    }

    // --- VIEWHOLDER CLASS ---

    public static class MediaViewHolder extends RecyclerView.ViewHolder {
        ImageView imgReviewMedia;
        ImageView imgPlayIcon;
        ImageView btnDelete;

        public MediaViewHolder(@NonNull View itemView) {
            super(itemView);
            imgReviewMedia = itemView.findViewById(R.id.img_media_thumbnail);
            imgPlayIcon = itemView.findViewById(R.id.img_video_play_icon);
            btnDelete = itemView.findViewById(R.id.btn_delete_media);
        }
    }
}