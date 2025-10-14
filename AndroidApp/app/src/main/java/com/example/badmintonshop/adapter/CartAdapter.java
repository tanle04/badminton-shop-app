package com.example.badmintonshop.adapter;

import android.content.Context;
import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.widget.CheckBox;
import android.widget.ImageButton;
import android.widget.ImageView;
import android.widget.TextView;
import androidx.annotation.NonNull;
import androidx.constraintlayout.widget.ConstraintLayout;
import androidx.recyclerview.widget.RecyclerView;
import com.bumptech.glide.Glide;
import com.example.badmintonshop.R;
import com.example.badmintonshop.model.CartItem;
import java.util.List;
import java.util.Locale;

public class CartAdapter extends RecyclerView.Adapter<CartAdapter.CartViewHolder> {

    // 1. Định nghĩa interface để giao tiếp với Activity
    public interface CartAdapterListener {
        void onUpdateQuantity(int cartId, int newQuantity);
        void onRemoveItem(int cartId, String productName);
        void onItemSelectedChanged();
        void onItemEditClicked(CartItem item); // 🚩 THÊM: Sự kiện khi nhấn vào item để sửa
    }

    private List<CartItem> cartItems;
    private final Context context;
    private final CartAdapterListener listener;

    public CartAdapter(Context context, List<CartItem> cartItems, CartAdapterListener listener) {
        this.context = context;
        this.cartItems = cartItems;
        this.listener = listener;
    }

    @NonNull
    @Override
    public CartViewHolder onCreateViewHolder(@NonNull ViewGroup parent, int viewType) {
        View view = LayoutInflater.from(context).inflate(R.layout.cart_item_layout, parent, false);
        return new CartViewHolder(view);
    }

    @Override
    public void onBindViewHolder(@NonNull CartViewHolder holder, int position) {
        CartItem item = cartItems.get(position);

        // Gán dữ liệu
        holder.name.setText(item.getProductName());
        holder.variant.setText(item.getVariantDetails());
        holder.price.setText(String.format(Locale.GERMAN, "%,.0f đ", Double.parseDouble(item.getVariantPrice())));
        holder.quantity.setText(String.valueOf(item.getQuantity()));
        holder.checkbox.setChecked(item.isSelected());

        // Tải ảnh
        String imageUrl = "http://10.0.2.2/api/BadmintonShop/images/uploads/" + item.getImageUrl();
        Glide.with(context)
                .load(imageUrl)
                .placeholder(R.drawable.ic_badminton_logo)
                .error(R.drawable.ic_badminton_logo)
                .into(holder.image);

        // --- XỬ LÝ SỰ KIỆN CLICK ---

        // Nút tăng số lượng
        holder.btnIncrease.setOnClickListener(v -> {
            int newQuantity = item.getQuantity() + 1;
            listener.onUpdateQuantity(item.getCartID(), newQuantity);
        });

        // Nút giảm số lượng
        holder.btnDecrease.setOnClickListener(v -> {
            int currentQuantity = item.getQuantity();
            if (currentQuantity > 1) {
                listener.onUpdateQuantity(item.getCartID(), currentQuantity - 1);
            } else {
                // Nếu số lượng là 1, báo cho Activity hiển thị dialog xác nhận xóa
                listener.onRemoveItem(item.getCartID(), item.getProductName());
            }
        });

        // Checkbox
        holder.checkbox.setOnCheckedChangeListener((buttonView, isChecked) -> {
            // Chỉ kích hoạt khi người dùng tự tay nhấn, không phải do code
            if (buttonView.isPressed()) {
                item.setSelected(isChecked);
                listener.onItemSelectedChanged(); // Báo cho Activity biết để tính lại tổng tiền
            }
        });

        // 🚩 THÊM: Sự kiện click vào toàn bộ item để mở popup chỉnh sửa
        holder.rootLayout.setOnClickListener(v -> {
            listener.onItemEditClicked(item);
        });
    }

    @Override
    public int getItemCount() {
        return cartItems != null ? cartItems.size() : 0;
    }

    public void updateData(List<CartItem> newItems) {
        this.cartItems = newItems;
        notifyDataSetChanged();
    }

    public static class CartViewHolder extends RecyclerView.ViewHolder {
        CheckBox checkbox;
        ImageView image;
        TextView name, variant, price, quantity;
        ImageButton btnIncrease, btnDecrease;
        ConstraintLayout rootLayout; // 🚩 THÊM: Tham chiếu đến layout gốc

        public CartViewHolder(@NonNull View itemView) {
            super(itemView);
            checkbox = itemView.findViewById(R.id.checkbox_product);
            image = itemView.findViewById(R.id.image_product);
            name = itemView.findViewById(R.id.text_product_name);
            variant = itemView.findViewById(R.id.text_product_variant);
            price = itemView.findViewById(R.id.text_product_price);
            quantity = itemView.findViewById(R.id.text_quantity);
            btnIncrease = itemView.findViewById(R.id.button_increase);
            btnDecrease = itemView.findViewById(R.id.button_decrease);
            rootLayout = itemView.findViewById(R.id.root_layout); // 🚩 Ánh xạ layout gốc
        }
    }
}