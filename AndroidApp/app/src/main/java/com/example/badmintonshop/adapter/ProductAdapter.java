package com.example.badmintonshop.adapter;

import android.content.Context;
import android.content.Intent;
import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.widget.ImageView;
import android.widget.TextView;

import androidx.annotation.NonNull;
import androidx.recyclerview.widget.RecyclerView;

import com.bumptech.glide.Glide;
import com.bumptech.glide.load.resource.drawable.DrawableTransitionOptions;
import com.example.badmintonshop.R;
import com.example.badmintonshop.network.dto.ProductDto;
import com.example.badmintonshop.ui.ProductDetailActivity;

import java.util.List;

public class ProductAdapter extends RecyclerView.Adapter<ProductAdapter.ViewHolder> {

    private final Context ctx;
    private final List<ProductDto> items;
    private static final String BASE_IMAGE_URL = "http://10.0.2.2/api/BadmintonShop/images/uploads/";

    public ProductAdapter(Context ctx, List<ProductDto> items) {
        this.ctx = ctx;
        this.items = items;
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

        // ✅ Tên sản phẩm
        h.tvName.setText(p.getProductName());

        // ✅ Giá
        h.tvPrice.setText(String.format("%,.0f ₫", p.getPrice()));

        // ✅ Thương hiệu
        h.tvBrand.setText(p.getBrandName() != null ? p.getBrandName() : "Unknown");

        // ✅ Ảnh sản phẩm
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

        // ✅ Sự kiện click -> mở ProductDetailActivity
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

    static class ViewHolder extends RecyclerView.ViewHolder {
        ImageView img;
        TextView tvName, tvPrice, tvBrand;

        ViewHolder(View v) {
            super(v);
            img = v.findViewById(R.id.imgProduct);
            tvName = v.findViewById(R.id.tvName);
            tvPrice = v.findViewById(R.id.tvPrice);
            tvBrand = v.findViewById(R.id.tvBrand);
        }
    }
}
