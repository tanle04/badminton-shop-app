package com.example.badmintonshop.adapter;

import android.content.Context;
import android.graphics.Bitmap;
import android.graphics.BitmapFactory;
import android.util.Base64;
import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.widget.ImageButton;
import android.widget.ImageView;

import androidx.annotation.NonNull;
import androidx.recyclerview.widget.RecyclerView;

import com.example.badmintonshop.R;

import java.util.List;

public class SelectedImageAdapter extends RecyclerView.Adapter<SelectedImageAdapter.ImageViewHolder> {

    // Interface để báo cho Activity biết khi nào user bấm xóa
    public interface OnImageRemoveListener {
        void onImageRemoved(int position);
    }

    private Context context;
    private List<String> base64Images;
    private OnImageRemoveListener listener;

    public SelectedImageAdapter(Context context, List<String> base64Images, OnImageRemoveListener listener) {
        this.context = context;
        this.base64Images = base64Images;
        this.listener = listener;
    }

    @NonNull
    @Override
    public ImageViewHolder onCreateViewHolder(@NonNull ViewGroup parent, int viewType) {
        View view = LayoutInflater.from(context).inflate(R.layout.item_selected_image, parent, false);
        return new ImageViewHolder(view);
    }

    @Override
    public void onBindViewHolder(@NonNull ImageViewHolder holder, int position) {
        String base64String = base64Images.get(position);

        try {
            // Giải mã chuỗi Base64 về Bitmap
            byte[] decodedString = Base64.decode(base64String, Base64.DEFAULT);
            Bitmap decodedByte = BitmapFactory.decodeByteArray(decodedString, 0, decodedString.length);
            holder.imgSelected.setImageBitmap(decodedByte);
        } catch (Exception e) {
            // Nếu lỗi, hiển thị ảnh placeholder
            holder.imgSelected.setImageResource(R.drawable.placeholder_image);
        }
    }

    @Override
    public int getItemCount() {
        return base64Images.size();
    }

    class ImageViewHolder extends RecyclerView.ViewHolder {
        ImageView imgSelected;
        ImageButton btnRemove;

        ImageViewHolder(@NonNull View itemView) {
            super(itemView);
            imgSelected = itemView.findViewById(R.id.img_selected);
            btnRemove = itemView.findViewById(R.id.btn_remove_image);

            // Bắt sự kiện bấm nút Xóa
            btnRemove.setOnClickListener(v -> {
                int pos = getAdapterPosition();
                if (listener != null && pos != RecyclerView.NO_POSITION) {
                    listener.onImageRemoved(pos);
                }
            });
        }
    }
}