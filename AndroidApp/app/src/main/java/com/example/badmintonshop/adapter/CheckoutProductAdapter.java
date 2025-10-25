package com.example.badmintonshop.adapter;

import android.content.Context;
import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.widget.ImageView;
import android.widget.TextView;

import androidx.annotation.NonNull;
import androidx.recyclerview.widget.RecyclerView;

import com.bumptech.glide.Glide;
import com.example.badmintonshop.R;
import com.example.badmintonshop.model.CartItem;

import java.util.List;
import java.util.Locale;

public class CheckoutProductAdapter extends RecyclerView.Adapter<CheckoutProductAdapter.ViewHolder> {

    private final List<CartItem> productList;
    private final Context context;
    // Khuyến nghị: Thay thế URL cứng bằng hằng số từ ApiClient/Constants
    private static final String BASE_IMAGE_URL = "http://10.0.2.2/api/BadmintonShop/images/";

    public CheckoutProductAdapter(Context context, List<CartItem> productList) {
        this.context = context;
        this.productList = productList;
    }

    @NonNull
    @Override
    public ViewHolder onCreateViewHolder(@NonNull ViewGroup parent, int viewType) {
        View view = LayoutInflater.from(context).inflate(R.layout.item_checkout_product, parent, false);
        return new ViewHolder(view);
    }

    @Override
    public void onBindViewHolder(@NonNull ViewHolder holder, int position) {
        CartItem item = productList.get(position);

        // --- BINDING DATA ---
        holder.productName.setText(item.getProductName());
        holder.productVariant.setText(item.getVariantDetails());

        // Chuyển đổi giá từ String sang Double để định dạng tiền tệ
        double priceValue = 0.0;
        try {
            priceValue = Double.parseDouble(item.getVariantPrice());
        } catch (NumberFormatException e) {
            // Log lỗi nếu giá không phải là số
            e.printStackTrace();
        }

        holder.productPrice.setText(String.format(Locale.GERMAN, "%,.0f đ", priceValue));
        holder.quantity.setText("x" + item.getQuantity());

        // --- LOAD IMAGE ---
        String imageUrl = BASE_IMAGE_URL + item.getImageUrl();
        Glide.with(context)
                .load(imageUrl)
                .placeholder(R.drawable.ic_badminton_logo)
                .error(R.drawable.ic_badminton_logo)
                .into(holder.productImage);
    }

    @Override
    public int getItemCount() {
        return productList != null ? productList.size() : 0;
    }

    // ViewHolder class to hold the views for each item
    public static class ViewHolder extends RecyclerView.ViewHolder {
        ImageView productImage;
        TextView productName, productVariant, productPrice, quantity;

        public ViewHolder(@NonNull View itemView) {
            super(itemView);
            productImage = itemView.findViewById(R.id.image_product);
            productName = itemView.findViewById(R.id.text_product_name);
            productVariant = itemView.findViewById(R.id.text_product_variant);
            productPrice = itemView.findViewById(R.id.text_product_price);
            quantity = itemView.findViewById(R.id.text_quantity);
        }
    }
}