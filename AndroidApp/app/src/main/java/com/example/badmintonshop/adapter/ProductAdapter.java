package com.example.badmintonshop.adapter;

import android.content.Context;
import android.content.Intent;
import android.graphics.Paint; // ⭐ Import cần thiết cho gạch ngang
import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.widget.ImageView;
import android.widget.TextView;

import androidx.annotation.NonNull;
import androidx.core.content.ContextCompat;
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
    // ⭐ Định nghĩa màu cho Sale và mặc định (Cần có trong resources/values/colors.xml)
    private static final int COLOR_SALE = R.color.red_sale; // Bạn cần định nghĩa màu này
    private static final int COLOR_DEFAULT = R.color.default_text; // Bạn cần định nghĩa màu này
    private static final int COLOR_ORIGINAL = R.color.gray_strikethrough; // Màu xám cho giá bị gạch


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

        // ✅ Tên sản phẩm, Thương hiệu, Ảnh (giữ nguyên)
        h.tvName.setText(p.getProductName());
        h.tvBrand.setText(p.getBrandName() != null ? p.getBrandName() : "Unknown");

        // ⭐ BẮT ĐẦU LOGIC XỬ LÝ GIÁ SALE MỚI
        if (p.isDiscounted()) {
            // 1. Nếu có sale: Hiển thị giá gốc bị gạch ngang
            h.tvOriginalPrice.setText(formatCurrency(p.getOriginalPriceMin()));
            // Áp dụng hiệu ứng gạch ngang
            h.tvOriginalPrice.setPaintFlags(h.tvOriginalPrice.getPaintFlags() | Paint.STRIKE_THRU_TEXT_FLAG);
            h.tvOriginalPrice.setTextColor(ContextCompat.getColor(ctx, COLOR_ORIGINAL));
            h.tvOriginalPrice.setVisibility(View.VISIBLE);

            // 2. Hiển thị giá sale (price hiện tại) với màu nổi bật
            h.tvPrice.setText(formatCurrency(p.getPrice()));
            h.tvPrice.setTextColor(ContextCompat.getColor(ctx, COLOR_SALE));

        } else {
            // 3. Nếu không có sale: Ẩn giá gốc, hiển thị giá bình thường
            h.tvOriginalPrice.setVisibility(View.GONE);
            h.tvPrice.setText(formatCurrency(p.getPrice()));
            // Loại bỏ gạch ngang và dùng màu mặc định
            h.tvPrice.setPaintFlags(h.tvPrice.getPaintFlags() & ~Paint.STRIKE_THRU_TEXT_FLAG);
            h.tvPrice.setTextColor(ContextCompat.getColor(ctx, COLOR_DEFAULT));
        }
        // ⭐ KẾT THÚC LOGIC XỬ LÝ GIÁ SALE

        // Load ảnh (giữ nguyên)
        String imgUrl = p.getImageUrl();
        if (imgUrl != null && !imgUrl.isEmpty()) {
            Glide.with(ctx)
                    .load( imgUrl)
                    .placeholder(R.drawable.ic_badminton_logo)
                    .error(R.drawable.ic_badminton_logo)
                    .transition(DrawableTransitionOptions.withCrossFade())
                    .into(h.img);
        } else {
            h.img.setImageResource(R.drawable.ic_badminton_logo);
        }

        // Logic Wishlist (giữ nguyên)
        if (favoriteIds.contains(p.getProductID())) {
            h.btnWishlist.setImageResource(R.drawable.ic_favorite_filled);
        } else {
            h.btnWishlist.setImageResource(R.drawable.ic_favorite);
        }

        // Sự kiện click nút Wishlist (giữ nguyên)
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
            // ⚠️ Cần truyền giá sale vào intent
            i.putExtra("productPrice", p.getPrice());
            i.putExtra("productBrand", p.getBrandName());
            i.putExtra("productImage",  p.getImageUrl());
            i.putExtra("productDesc", p.getDescription());
            ctx.startActivity(i);
        });
    }

    @Override
    public int getItemCount() {
        return items != null ? items.size() : 0;
    }

    public void updateData(List<ProductDto> newItems) {
        this.items.clear();
        if (newItems != null) {
            this.items.addAll(newItems);
        }
        notifyDataSetChanged();
    }

    // ⭐ HÀM CHUYỂN ĐỔI TIỀN TỆ MỚI
    private String formatCurrency(double price) {
        // Sử dụng định dạng tiền tệ Việt Nam đồng
        return String.format("%,.0f ₫", price);
    }

    static class ViewHolder extends RecyclerView.ViewHolder {
        ImageView img;
        TextView tvName, tvPrice, tvBrand;
        TextView tvOriginalPrice; // ⭐ THÊM TextView cho giá gốc
        ImageView btnWishlist;

        ViewHolder(View v) {
            super(v);
            img = v.findViewById(R.id.imgProduct);
            tvName = v.findViewById(R.id.tvName);
            tvPrice = v.findViewById(R.id.tvPrice);
            tvBrand = v.findViewById(R.id.tvBrand);
            btnWishlist = v.findViewById(R.id.btnWishlist);

            // ⭐ ÁNH XẠ TextView MỚI
            tvOriginalPrice = v.findViewById(R.id.tv_original_price);
        }
    }
}