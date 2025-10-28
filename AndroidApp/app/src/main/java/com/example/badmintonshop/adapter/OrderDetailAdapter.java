package com.example.badmintonshop.adapter;

import android.content.Context;
import android.graphics.Paint;
import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.widget.ImageView;
import android.widget.TextView;

import androidx.annotation.NonNull;
import androidx.core.content.ContextCompat;
import androidx.recyclerview.widget.RecyclerView;

import com.bumptech.glide.Glide;
import com.example.badmintonshop.R;
import com.example.badmintonshop.network.dto.OrderDetailDto;

import java.util.List;
import java.util.Locale;

public class OrderDetailAdapter extends RecyclerView.Adapter<OrderDetailAdapter.DetailViewHolder> {

    private final Context context;
    private final List<OrderDetailDto> itemList;
    private static final String BASE_IMAGE_URL = "http://10.0.2.2/api/BadmintonShop/images/";

    // ⭐ Định nghĩa màu Sale (Cần định nghĩa trong colors.xml)
    private static final int COLOR_SALE = R.color.red_sale;
    private static final int COLOR_ORIGINAL = R.color.gray_strikethrough;
    private static final int COLOR_DEFAULT = R.color.default_text;

    public OrderDetailAdapter(Context context, List<OrderDetailDto> itemList) {
        this.context = context;
        this.itemList = itemList;
    }

    private String formatCurrency(double price) {
        return String.format(Locale.GERMAN, "%,.0f đ", price);
    }

    @NonNull
    @Override
    public DetailViewHolder onCreateViewHolder(@NonNull ViewGroup parent, int viewType) {
        // Sử dụng layout đã tạo cho mỗi item sản phẩm
        View view = LayoutInflater.from(context).inflate(R.layout.item_order_detail_product, parent, false);
        return new DetailViewHolder(view);
    }
    public void updateData(List<OrderDetailDto> newItemList) {
        // Xóa danh sách cũ (LƯU Ý: Phải xóa trực tiếp list cũ, không tạo list mới)
        if (this.itemList != null) {
            this.itemList.clear();
            // Thêm tất cả item từ danh sách mới vào
            if (newItemList != null) {
                this.itemList.addAll(newItemList);
            }
        }
        // Thông báo cho Adapter biết dữ liệu đã thay đổi hoàn toàn
        notifyDataSetChanged();
    }

    @Override
    public void onBindViewHolder(@NonNull DetailViewHolder holder, int position) {
        OrderDetailDto item = itemList.get(position);

        double itemPrice = item.getPrice(); // Giá mua (đã sale)
        double originalPrice = item.getOriginalPrice(); // Giá gốc hiện tại (để tham khảo)
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

        // Cờ isDiscounted được tính bằng cách so sánh giá mua (od.price) với giá gốc hiện tại
        if (item.isDiscounted()) {
            // Có Sale: Hiển thị giá gốc bị gạch ngang
            holder.tvOriginalPrice.setText(formatCurrency(originalPrice));
            holder.tvOriginalPrice.setPaintFlags(holder.tvOriginalPrice.getPaintFlags() | Paint.STRIKE_THRU_TEXT_FLAG);
            holder.tvOriginalPrice.setTextColor(ContextCompat.getColor(context, COLOR_ORIGINAL));
            holder.tvOriginalPrice.setVisibility(View.VISIBLE);

            // Hiển thị giá đã giảm (Giá cuối cùng khi mua)
            holder.tvPrice.setText(formatCurrency(itemPrice));
            holder.tvPrice.setTextColor(ContextCompat.getColor(context, COLOR_SALE));
        } else {
            // Không có giảm giá, chỉ hiển thị giá mua (giá gốc)
            holder.tvOriginalPrice.setVisibility(View.GONE);
            holder.tvPrice.setText(formatCurrency(itemPrice));
            holder.tvPrice.setTextColor(ContextCompat.getColor(context, COLOR_DEFAULT));
            holder.tvPrice.setPaintFlags(0); // Đảm bảo bỏ gạch ngang
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
        TextView tvOriginalPrice;
        TextView tvPrice;
        TextView tvQuantity;

        public DetailViewHolder(@NonNull View itemView) {
            super(itemView);
            imgProduct = itemView.findViewById(R.id.img_product);
            tvProductName = itemView.findViewById(R.id.tv_product_name);
            tvVariantDetails = itemView.findViewById(R.id.tv_variant_details);

            // ⭐ Đảm bảo ID này tồn tại trong item_order_detail_product.xml
            tvOriginalPrice = itemView.findViewById(R.id.tv_original_price);

            tvPrice = itemView.findViewById(R.id.tv_price);
            tvQuantity = itemView.findViewById(R.id.tv_quantity);
        }
    }
}