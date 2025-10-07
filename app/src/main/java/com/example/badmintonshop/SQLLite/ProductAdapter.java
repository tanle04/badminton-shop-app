// ui/ProductAdapter.java
package com.example.badmintonshop.SQLLite;

import android.view.*;
import android.widget.ImageView;
import android.widget.TextView;

import androidx.annotation.NonNull;
import androidx.recyclerview.widget.RecyclerView;

import com.example.badmintonshop.R;
import com.example.badmintonshop.model.Product;

import java.util.List;

public class ProductAdapter extends RecyclerView.Adapter<ProductAdapter.VH> {
    private final List<Product> data;

    public ProductAdapter(List<Product> data) { this.data = data; }

    @NonNull @Override public VH onCreateViewHolder(@NonNull ViewGroup parent, int viewType) {
        View v = LayoutInflater.from(parent.getContext())
                .inflate(R.layout.item_product, parent, false);
        return new VH(v);
    }

    @Override public void onBindViewHolder(@NonNull VH h, int i) {
        Product p = data.get(i);
        h.name.setText(p.productName);
        h.price.setText(String.format("%,.0fđ", p.price));
        // h.image: với ảnh http bạn dùng Glide/Picasso; demo tạm bỏ qua
    }

    @Override public int getItemCount() { return data.size(); }

    static class VH extends RecyclerView.ViewHolder {
        TextView name, price; ImageView image;
        VH(View v){ super(v);
            name = v.findViewById(R.id.tvName);
            price= v.findViewById(R.id.tvPrice);
            image= v.findViewById(R.id.imgProduct);
        }
    }
}
