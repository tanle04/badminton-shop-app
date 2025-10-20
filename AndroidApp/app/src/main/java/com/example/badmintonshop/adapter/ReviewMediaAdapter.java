package com.example.badmintonshop.adapter;

import android.content.Context;
import android.net.Uri;
import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.widget.ImageView;
import androidx.annotation.NonNull;
import androidx.recyclerview.widget.RecyclerView;
import com.bumptech.glide.Glide;
import com.example.badmintonshop.R;

import java.util.List;

public class ReviewMediaAdapter extends RecyclerView.Adapter<ReviewMediaAdapter.MediaViewHolder> {

    private final Context context;
    private final List<Uri> mediaUris;
    private final boolean isVideoSelected;
    // ⭐ Thêm Listener để xử lý sự kiện xóa tệp
    private final MediaDeleteListener deleteListener;

    // ⭐ Interface để giao tiếp với ReviewAdapter/ReviewActivity khi tệp bị xóa
    public interface MediaDeleteListener {
        // position: vị trí của media item trong danh sách mediaUris
        void onMediaDeleted(int mediaPosition);
    }

    // Constructor nhận danh sách Uri và Listener
    public ReviewMediaAdapter(Context context, List<Uri> mediaUris, boolean isVideoSelected, MediaDeleteListener deleteListener) {
        this.context = context;
        this.mediaUris = mediaUris;
        this.isVideoSelected = isVideoSelected;
        this.deleteListener = deleteListener;
    }

    @NonNull
    @Override
    public MediaViewHolder onCreateViewHolder(@NonNull ViewGroup parent, int viewType) {
        View view = LayoutInflater.from(context).inflate(R.layout.item_review_media, parent, false);
        return new MediaViewHolder(view);
    }

    @Override
    public void onBindViewHolder(@NonNull MediaViewHolder holder, int position) {
        Uri mediaUri = mediaUris.get(position);

        // 1. Tải thumbnail từ Uri
        Glide.with(context)
                .load(mediaUri)
                .placeholder(R.drawable.ic_badminton_logo)
                .error(R.drawable.ic_badminton_logo)
                .centerCrop() // Đảm bảo thumbnail vừa vặn
                .into(holder.imgThumbnail);

        // 2. Ẩn/hiện Icon Play
        // Chỉ hiển thị icon play nếu tệp này là video (isVideoSelected = true)
        if (isVideoSelected) {
            holder.imgPlayIcon.setVisibility(View.VISIBLE);
        } else {
            holder.imgPlayIcon.setVisibility(View.GONE);
        }

        // 3. Xử lý sự kiện xóa
        holder.btnDelete.setOnClickListener(v -> {
            if (deleteListener != null) {
                // Sử dụng getAdapterPosition() an toàn
                int currentPos = holder.getAdapterPosition();
                if (currentPos != RecyclerView.NO_POSITION) {
                    deleteListener.onMediaDeleted(currentPos);
                }
            }
        });
    }

    @Override
    public int getItemCount() {
        return mediaUris.size();
    }

    // --- VIEWHOLDER CLASS ---

    public static class MediaViewHolder extends RecyclerView.ViewHolder {
        ImageView imgThumbnail;
        ImageView imgPlayIcon;
        ImageView btnDelete;

        public MediaViewHolder(@NonNull View itemView) {
            super(itemView);
            imgThumbnail = itemView.findViewById(R.id.img_media_thumbnail);
            imgPlayIcon = itemView.findViewById(R.id.img_video_play_icon);
            btnDelete = itemView.findViewById(R.id.btn_delete_media);
        }
    }
}