package com.example.badmintonshop.adapter;

import android.content.Context;
import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.widget.ImageView;

import androidx.annotation.NonNull;
import androidx.recyclerview.widget.RecyclerView;

import com.bumptech.glide.Glide;
import com.bumptech.glide.load.resource.drawable.DrawableTransitionOptions;
import com.example.badmintonshop.R;
import com.example.badmintonshop.network.dto.ProductDto;

import java.util.List;

public class GalleryAdapter extends RecyclerView.Adapter<GalleryAdapter.ViewHolder> {

    private final Context context;
    private final List<ProductDto.ImageDto> images;
    private final OnImageClickListener listener;
    private final boolean isThumbnailMode; // ✅ chế độ hiển thị
    private int selectedPosition = 0;

//    private static final String BASE_IMAGE_URL = "http://10.0.2.2/api/BadmintonShop/images/";

    // -------------------- INTERFACE --------------------
    public interface OnImageClickListener {
        void onImageClick(String imageUrl);
    }

    // -------------------- CONSTRUCTOR --------------------
    // ✅ Constructor chính (cho phép chọn thumbnail mode)
    public GalleryAdapter(Context context, List<ProductDto.ImageDto> images,
                          OnImageClickListener listener, boolean isThumbnailMode) {
        this.context = context;
        this.images = images;
        this.listener = listener;
        this.isThumbnailMode = isThumbnailMode;
    }

    // ✅ Overload constructor (khi không cần thumbnail mode)
    public GalleryAdapter(Context context, List<ProductDto.ImageDto> images,
                          OnImageClickListener listener) {
        this(context, images, listener, false);
    }

    // -------------------- URL HANDLER --------------------
    private String normalize(String raw) {
        if (raw == null || raw.trim().isEmpty()) return null;
        raw = raw.trim();
        if (raw.startsWith("http")) {
            return raw.replace("/api/BadmintonShop/uploads/", "/api/BadmintonShop/images/");
        }
        raw = raw.replaceFirst("^/?(images/)?uploads/", "");
        return   raw;
    }

    // -------------------- VIEW HOLDER --------------------
    @NonNull
    @Override
    public ViewHolder onCreateViewHolder(@NonNull ViewGroup parent, int viewType) {
        int layoutId = isThumbnailMode ? R.layout.item_gallery_thumb : R.layout.item_gallery_image;
        View v = LayoutInflater.from(context).inflate(layoutId, parent, false);

        // Nếu là ảnh chính (ViewPager2), set full screen
        if (!isThumbnailMode) {
            ViewGroup.LayoutParams params = v.getLayoutParams();
            params.width = ViewGroup.LayoutParams.MATCH_PARENT;
            params.height = ViewGroup.LayoutParams.MATCH_PARENT;
            v.setLayoutParams(params);
        }

        return new ViewHolder(v);
    }

    // -------------------- BIND VIEW --------------------
    @Override
    public void onBindViewHolder(@NonNull ViewHolder h, int pos) {
        String url = normalize(images.get(pos).getImageUrl());

        Glide.with(context)
                .load(url)
                .placeholder(R.drawable.ic_badminton_logo)
                .error(R.drawable.ic_badminton_logo)
                .transition(DrawableTransitionOptions.withCrossFade())
                .into(h.imageView);

        // ✅ highlight chỉ khi là thumbnail
        if (isThumbnailMode && h.viewHighlight != null) {
            h.viewHighlight.setVisibility(pos == selectedPosition ? View.VISIBLE : View.GONE);
        }

        h.itemView.setOnClickListener(v -> {
            int position = h.getAdapterPosition();
            if (position != RecyclerView.NO_POSITION && listener != null) {
                if (isThumbnailMode) {
                    selectedPosition = position;
                    notifyDataSetChanged(); // cập nhật viền highlight
                }
                listener.onImageClick(normalize(images.get(position).getImageUrl()));
            }
        });
    }

    @Override
    public int getItemCount() {
        return images != null ? images.size() : 0;
    }

    // -------------------- VIEW HOLDER CLASS --------------------
    static class ViewHolder extends RecyclerView.ViewHolder {
        ImageView imageView;
        View viewHighlight;

        ViewHolder(View item) {
            super(item);
            imageView = item.findViewById(R.id.imgThumb);
            viewHighlight = item.findViewById(R.id.viewHighlight);
        }
    }
}
