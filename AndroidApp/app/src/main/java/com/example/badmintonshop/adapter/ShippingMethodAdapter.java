package com.example.badmintonshop.adapter;

import android.content.Context;
import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.widget.ImageView;
import android.widget.TextView;
import androidx.annotation.NonNull;
import androidx.core.content.ContextCompat;
import androidx.recyclerview.widget.RecyclerView;

import com.example.badmintonshop.R;
import com.example.badmintonshop.model.ShippingRate;

import java.util.List;
import java.util.Locale;

public class ShippingMethodAdapter extends RecyclerView.Adapter<ShippingMethodAdapter.ViewHolder> {

    private final List<ShippingRate> rateList;
    private final Context context;
    private int selectedRateId;
    private final OnRateSelectedListener listener;

    public interface OnRateSelectedListener {
        void onRateSelected(ShippingRate rate);
    }

    public ShippingMethodAdapter(Context context, List<ShippingRate> rateList, int selectedRateId, OnRateSelectedListener listener) {
        this.context = context;
        this.rateList = rateList;
        this.selectedRateId = selectedRateId;
        this.listener = listener;
    }

    /**
     * Cập nhật ID phương thức vận chuyển được chọn và thông báo thay đổi.
     */
    public void setSelectedRateId(int rateId) {
        int oldSelectedId = this.selectedRateId;
        this.selectedRateId = rateId;
        notifyItemChanged(findPositionById(oldSelectedId));
        notifyItemChanged(findPositionById(this.selectedRateId));
    }


    @NonNull
    @Override
    public ViewHolder onCreateViewHolder(@NonNull ViewGroup parent, int viewType) {
        View view = LayoutInflater.from(context).inflate(R.layout.item_shipping_method, parent, false);
        return new ViewHolder(view);
    }

    @Override
    public void onBindViewHolder(@NonNull ViewHolder holder, int position) {
        ShippingRate rate = rateList.get(position);

        holder.serviceName.setText(rate.getServiceName());
        holder.estimatedDelivery.setText(rate.getEstimatedDelivery());

        // Hiển thị giá và gắn nhãn "Miễn phí" nếu giá bằng 0
        // Giả định colorTextPrimary và colorPrimary tồn tại trong colors.xml
        if (rate.getPrice() <= 0) {
            holder.price.setText("Miễn Phí");
            holder.price.setTextColor(ContextCompat.getColor(context, R.color.colorPrimary));
        } else {
            holder.price.setText(String.format(Locale.GERMAN, "%,.0f đ", rate.getPrice()));
            holder.price.setTextColor(ContextCompat.getColor(context, R.color.colorTextPrimary));
        }

        // Đánh dấu mục được chọn
        boolean isSelected = rate.getRateID() == selectedRateId;
        if (isSelected) {
            // Giả định colorSelectionBackground tồn tại trong colors.xml
            holder.itemView.setBackgroundColor(ContextCompat.getColor(context, R.color.colorSelectionBackground));
            holder.serviceName.setTextColor(ContextCompat.getColor(context, R.color.colorPrimary));
            holder.checkedIcon.setVisibility(View.VISIBLE);
            holder.price.setVisibility(View.GONE); // Ẩn giá để nhường chỗ cho icon check
        } else {
            holder.itemView.setBackgroundResource(android.R.color.transparent);
            holder.serviceName.setTextColor(ContextCompat.getColor(context, R.color.colorTextPrimary));
            holder.checkedIcon.setVisibility(View.GONE);
            holder.price.setVisibility(View.VISIBLE);
        }

        holder.itemView.setOnClickListener(v -> {
            if (rate.getRateID() != selectedRateId) {
                setSelectedRateId(rate.getRateID());
                listener.onRateSelected(rate);
            }
        });
    }

    @Override
    public int getItemCount() {
        return rateList != null ? rateList.size() : 0;
    }

    /**
     * Tìm vị trí (position) của một rate trong danh sách dựa trên ID.
     */
    private int findPositionById(int rateId) {
        for (int i = 0; i < rateList.size(); i++) {
            if (rateList.get(i).getRateID() == rateId) {
                return i;
            }
        }
        return RecyclerView.NO_POSITION;
    }


    public static class ViewHolder extends RecyclerView.ViewHolder {
        TextView serviceName, estimatedDelivery, price;
        ImageView checkedIcon;

        public ViewHolder(@NonNull View itemView) {
            super(itemView);
            serviceName = itemView.findViewById(R.id.tv_shipping_service_name);
            estimatedDelivery = itemView.findViewById(R.id.tv_estimated_delivery);
            price = itemView.findViewById(R.id.tv_shipping_price);
            checkedIcon = itemView.findViewById(R.id.img_checked);
        }
    }
}
