package com.example.badmintonshop.adapter;

import android.content.Context;
import android.graphics.Paint; // ⭐ ĐÃ THÊM: Cần cho việc gạch ngang giá
import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.widget.ImageView;
import android.widget.TextView;

import androidx.annotation.NonNull;
import androidx.recyclerview.widget.RecyclerView;

import com.bumptech.glide.Glide;
import com.example.badmintonshop.R;
import com.example.badmintonshop.network.dto.OrderDetailDto;

import java.util.List;
import java.util.Locale;

public class OrderDetailAdapter extends RecyclerView.Adapter<OrderDetailAdapter.DetailViewHolder> {

    private final Context context;
    private final List<OrderDetailDto> itemList;
    private static final String BASE_IMAGE_URL = "http://10.0.2.2/api/BadmintonShop/images/uploads/";

    public OrderDetailAdapter(Context context, List<OrderDetailDto> itemList) {
        this.context = context;
        this.itemList = itemList;
    }

    @NonNull
    @Override
    public DetailViewHolder onCreateViewHolder(@NonNull ViewGroup parent, int viewType) {
        // Sử dụng layout đã tạo cho mỗi item sản phẩm
        View view = LayoutInflater.from(context).inflate(R.layout.item_order_detail_product, parent, false);
        return new DetailViewHolder(view);
    }

    @Override
    public void onBindViewHolder(@NonNull DetailViewHolder holder, int position) {
        OrderDetailDto item = itemList.get(position);

        // Giả định giá gốc (chỉ để minh họa, cần lấy từ DTO thực tế)
        // Ví dụ: Giả định giảm 50.000 đ
        double originalPrice = item.getPrice() + 50000;
        double itemPrice = item.getPrice();
        int quantity = item.getQuantity();

        // 1. Tải ảnh sản phẩm
        String imageUrl = BASE_IMAGE_URL + item.getImageUrl();
        Glide.with(context)
                .load(imageUrl)
                .placeholder(R.drawable.ic_badminton_logo)
                .error(R.drawable.ic_badminton_logo)
                .into(holder.imgProduct);

        // 2. Tên và chi tiết sản phẩm
        holder.tvProductName.setText(item.getProductName());

        // 3. Chi tiết biến thể
        holder.tvVariantDetails.setText(String.format("Phân loại: %s", item.getVariantDetails()));

        // ⭐ 4. Giá và Số lượng (SỬA ĐỔI LOGIC GIÁ)

        if (originalPrice > itemPrice) {
            // Hiển thị giá gốc bị gạch ngang
            holder.tvOriginalPrice.setText(String.format(Locale.GERMAN, "%,.0f đ", originalPrice));
            holder.tvOriginalPrice.setPaintFlags(holder.tvOriginalPrice.getPaintFlags() | Paint.STRIKE_THRU_TEXT_FLAG);
            holder.tvOriginalPrice.setVisibility(View.VISIBLE);

            // Hiển thị giá đã giảm (Giá cuối cùng)
            holder.tvPrice.setText(String.format(Locale.GERMAN, "%,.0f đ", itemPrice));
        } else {
            // Không có giảm giá, chỉ hiển thị giá cuối cùng
            holder.tvOriginalPrice.setVisibility(View.GONE);
            holder.tvPrice.setText(String.format(Locale.GERMAN, "%,.0f đ", itemPrice));
        }

        // Hiển thị số lượng
        holder.tvQuantity.setText(String.format(Locale.getDefault(), "x%d", quantity));
    }

    @Override
    public int getItemCount() {
        return itemList != null ? itemList.size() : 0;
    }

    // --- VIEWHOLDER CLASS ---
    public static class DetailViewHolder extends RecyclerView.ViewHolder {
        ImageView imgProduct;
        TextView tvProductName;
        TextView tvVariantDetails;
        TextView tvOriginalPrice; // ⭐ Ánh xạ mới
        TextView tvPrice;
        TextView tvQuantity;

        public DetailViewHolder(@NonNull View itemView) {
            super(itemView);
            imgProduct = itemView.findViewById(R.id.img_product);
            tvProductName = itemView.findViewById(R.id.tv_product_name);
            tvVariantDetails = itemView.findViewById(R.id.tv_variant_details);
            tvOriginalPrice = itemView.findViewById(R.id.tv_original_price); // ⭐ Ánh xạ mới
            tvPrice = itemView.findViewById(R.id.tv_price);
            tvQuantity = itemView.findViewById(R.id.tv_quantity);
        }
    }
}