package com.example.badmintonshop.adapter;

import android.content.Context;
import android.content.Intent;
import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.widget.ImageView;
import android.widget.TextView;

import androidx.annotation.NonNull;
import androidx.core.content.ContextCompat; // Import cần thiết để lấy màu
import androidx.recyclerview.widget.RecyclerView;

import com.bumptech.glide.Glide;
import com.bumptech.glide.load.resource.drawable.DrawableTransitionOptions;
import com.example.badmintonshop.R;
import com.example.badmintonshop.network.dto.ProductDto;
import com.example.badmintonshop.ui.ProductDetailActivity;

import java.util.Collections;
import java.util.List;
import java.util.Set;

public class ProductAdapter extends RecyclerView.Adapter<ProductAdapter.ViewHolder> {

    // 1. Định nghĩa Interface Listener cho nút Wishlist
    public interface OnWishlistClickListener {
        void onWishlistClick(ProductDto product);
    }

    private final Context ctx;
    private final List<ProductDto> items;
    private final OnWishlistClickListener wishlistClickListener;
    // 🚩 Biến lưu trữ ID sản phẩm yêu thích để kiểm tra trạng thái
    private final Set<Integer> favoriteIds;

    private static final String BASE_IMAGE_URL = "http://10.0.2.2/api/BadmintonShop/images/";

    // Constructor CŨ (Giữ lại để tương thích)
    public ProductAdapter(Context ctx, List<ProductDto> items) {
        // Mặc định list IDs rỗng nếu không truyền vào
        this(ctx, items, null, Collections.emptySet());
    }

    // 3. Constructor MỚI để truyền Wishlist Listener VÀ List ID yêu thích
    public ProductAdapter(Context ctx, List<ProductDto> items, OnWishlistClickListener listener, Set<Integer> favoriteIds) {
        this.ctx = ctx;
        this.items = items;
        this.wishlistClickListener = listener;
        this.favoriteIds = favoriteIds != null ? favoriteIds : Collections.emptySet(); // 🚩 Lưu Set ID
    }

    @NonNull
    @Override
    public ViewHolder onCreateViewHolder(@NonNull ViewGroup parent, int viewType) {
        View v = LayoutInflater.from(ctx).inflate(R.layout.item_product, parent, false);
        return new ViewHolder(v);
    }

    @Override
    public void onBindViewHolder(@NonNull ViewHolder h, int pos) {
        ProductDto p = items.get(pos);

        // ✅ Tên sản phẩm, Giá, Thương hiệu, Ảnh (giữ nguyên)
        h.tvName.setText(p.getProductName());
        h.tvPrice.setText(String.format("%,.0f ₫", p.getPrice()));
        h.tvBrand.setText(p.getBrandName() != null ? p.getBrandName() : "Unknown");

        String imgUrl = p.getImageUrl();
        if (imgUrl != null && !imgUrl.isEmpty()) {
            Glide.with(ctx)
                    .load(BASE_IMAGE_URL + imgUrl)
                    .placeholder(R.drawable.ic_badminton_logo)
                    .error(R.drawable.ic_badminton_logo)
                    .transition(DrawableTransitionOptions.withCrossFade())
                    .into(h.img);
        } else {
            h.img.setImageResource(R.drawable.ic_badminton_logo);
        }

        // 🚩 LOGIC CẬP NHẬT MÀU NÚT WISHLIST
        if (favoriteIds.contains(p.getProductID())) {
            // Đã yêu thích: Dùng trái tim đầy (Đỏ)
            // (Bạn cần đảm bảo R.drawable.ic_favorite_filled tồn tại trong drawable)
            h.btnWishlist.setImageResource(R.drawable.ic_favorite_filled);
        } else {
            // Chưa yêu thích: Dùng trái tim rỗng (Trắng/Xám)
            h.btnWishlist.setImageResource(R.drawable.ic_favorite);
        }

        // Sự kiện click nút Wishlist (giữ nguyên, gọi toggleWishlist)
        h.btnWishlist.setOnClickListener(v -> {
            if (wishlistClickListener != null) {
                wishlistClickListener.onWishlistClick(p);
            }
        });


        // ✅ Sự kiện click item -> mở ProductDetailActivity (giữ nguyên)
        h.itemView.setOnClickListener(v -> {
            Intent i = new Intent(ctx, ProductDetailActivity.class);
            i.putExtra("productID", p.getProductID());
            i.putExtra("productName", p.getProductName());
            i.putExtra("productPrice", p.getPrice());
            i.putExtra("productBrand", p.getBrandName());
            i.putExtra("productImage", BASE_IMAGE_URL + p.getImageUrl());
            i.putExtra("productDesc", p.getDescription());
            ctx.startActivity(i);
        });
    }

    @Override
    public int getItemCount() {
        return items != null ? items.size() : 0;
    }
    // 🚩 NEW: Thêm phương thức này vào cuối lớp
    public void updateData(List<ProductDto> newItems) {
        this.items.clear(); // Xóa dữ liệu cũ
        if (newItems != null) {
            this.items.addAll(newItems); // Thêm dữ liệu mới
        }
        notifyDataSetChanged(); // Báo cho Adapter biết dữ liệu đã thay đổi
    }
    static class ViewHolder extends RecyclerView.ViewHolder {
        ImageView img;
        TextView tvName, tvPrice, tvBrand;
        ImageView btnWishlist;

        ViewHolder(View v) {
            super(v);
            img = v.findViewById(R.id.imgProduct);
            tvName = v.findViewById(R.id.tvName);
            tvPrice = v.findViewById(R.id.tvPrice);
            tvBrand = v.findViewById(R.id.tvBrand);
            btnWishlist = v.findViewById(R.id.btnWishlist);
        }
    }
}
