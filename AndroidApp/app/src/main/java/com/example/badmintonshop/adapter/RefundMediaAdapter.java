package com.example.badmintonshop.adapter;

import android.content.Context;
import android.net.Uri;
import android.util.Log; // ⭐ THÊM IMPORT
import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.widget.ImageButton;
import android.widget.ImageView;

import androidx.annotation.NonNull;
import androidx.recyclerview.widget.RecyclerView;

import com.bumptech.glide.Glide;
import com.example.badmintonshop.R;

import java.util.ArrayList;
import java.util.List;

public class RefundMediaAdapter extends RecyclerView.Adapter<RefundMediaAdapter.MediaViewHolder> {

    // ⭐ THÊM LOG TAG
    private static final String TAG = "RefundMediaAdapter";

    public interface OnMediaRemoveListener {
        void onMediaRemoved(Uri uri);
    }

    private final Context context;
    private final List<Uri> mediaUris;
    private final OnMediaRemoveListener listener;
    private final List<Uri> photoUrisRef;
    private final List<Uri> videoUrisRef;

    public RefundMediaAdapter(Context context, List<Uri> photoUris, List<Uri> videoUris, OnMediaRemoveListener listener) {
        this.context = context;
        this.photoUrisRef = photoUris;
        this.videoUrisRef = videoUris;
        this.listener = listener;
        this.mediaUris = new ArrayList<>();

        Log.d(TAG, "Adapter Constructor: Khởi tạo với " + photoUris.size() + " ảnh, " + videoUris.size() + " video.");

        updateMediaUris(); // Chạy lần đầu
    }


    public void updateMediaUris() {
        int oldSize = mediaUris.size();
        mediaUris.clear();
        if (oldSize > 0) {
            // ⭐ THÊM LOG
            Log.d(TAG, "updateMediaUris: Đã xóa " + oldSize + " item(s).");
            notifyItemRangeRemoved(0, oldSize);
        }

        mediaUris.addAll(photoUrisRef);
        mediaUris.addAll(videoUrisRef);

        int newSize = mediaUris.size();
        if (newSize > 0) {
            // ⭐ THÊM LOG
            Log.d(TAG, "updateMediaUris: Thêm " + newSize + " item(s) mới.");
            notifyItemRangeInserted(0, newSize);
        }

        if (newSize == 0 && oldSize == 0) {
            Log.d(TAG, "updateMediaUris: Không có gì thay đổi (0 items).");
        }
    }


    @NonNull
    @Override
    public MediaViewHolder onCreateViewHolder(@NonNull ViewGroup parent, int viewType) {
        // ⭐ THÊM LOG
        Log.d(TAG, "onCreateViewHolder: Đang tạo 1 view media mới...");
        View view = LayoutInflater.from(context).inflate(R.layout.item_selected_media, parent, false);
        return new MediaViewHolder(view);
    }

    @Override
    public void onBindViewHolder(@NonNull MediaViewHolder holder, int position) {
        Uri uri = mediaUris.get(position);
        boolean isVideo = videoUrisRef.contains(uri);

        // ⭐ THÊM LOG
        Log.d(TAG, "onBindViewHolder: Đang VẼ item tại vị trí " + position + ". IsVideo: " + isVideo);
        Log.d(TAG, "URI: " + uri.toString());

        if (isVideo) {
            holder.imgVideoOverlay.setVisibility(View.VISIBLE);
        } else {
            holder.imgVideoOverlay.setVisibility(View.GONE);
        }

        Glide.with(context)
                .load(uri)
                .placeholder(R.drawable.placeholder_image)
                .error(R.drawable.placeholder_image)
                .centerCrop()
                .into(holder.imgMedia);
    }

    @Override
    public int getItemCount() {
        // ⭐ THÊM LOG
        int count = mediaUris.size();
        Log.d(TAG, "getItemCount: " + count);
        return count;
    }

    class MediaViewHolder extends RecyclerView.ViewHolder {
        ImageView imgMedia, imgVideoOverlay;
        ImageButton btnRemove;

        MediaViewHolder(@NonNull View itemView) {
            super(itemView);
            imgMedia = itemView.findViewById(R.id.img_selected_media);
            imgVideoOverlay = itemView.findViewById(R.id.img_video_overlay);
            btnRemove = itemView.findViewById(R.id.btn_remove_media);

            btnRemove.setOnClickListener(v -> {
                int pos = getAdapterPosition();
                if (listener != null && pos != RecyclerView.NO_POSITION) {
                    listener.onMediaRemoved(mediaUris.get(pos));
                }
            });
        }
    }
}