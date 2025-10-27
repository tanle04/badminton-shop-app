package com.example.badmintonshop.adapter;

import android.content.Context;
import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.widget.RadioButton;
import android.widget.TextView;
import androidx.annotation.NonNull;
import androidx.recyclerview.widget.RecyclerView;

import com.example.badmintonshop.R;
import com.example.badmintonshop.network.dto.ShippingRateDto;

import java.util.List;
import java.util.Locale;

public class ShippingRateAdapter extends RecyclerView.Adapter<ShippingRateAdapter.RateViewHolder> {

    private final List<ShippingRateDto> rates;
    private int selectedPosition = RecyclerView.NO_POSITION;
    private final OnRateSelectedListener listener;
    private final Context context;

    public interface OnRateSelectedListener {
        void onRateSelected(ShippingRateDto rate);
    }

    public ShippingRateAdapter(Context context, List<ShippingRateDto> rates, OnRateSelectedListener listener) {
        this.context = context;
        this.rates = rates;
        this.listener = listener;
    }

    @NonNull
    @Override
    public RateViewHolder onCreateViewHolder(@NonNull ViewGroup parent, int viewType) {
        View view = LayoutInflater.from(parent.getContext()).inflate(R.layout.item_shipping_rate, parent, false);
        return new RateViewHolder(view);
    }

    @Override
    public void onBindViewHolder(@NonNull RateViewHolder holder, int position) {
        final ShippingRateDto rate = rates.get(position); // Sử dụng final cho rate

        // Tên Carrier và Service Name
        holder.tvRateName.setText(String.format("%s - %s", rate.getCarrierName(), rate.getServiceName()));

        // Thời gian giao hàng
        holder.tvEstimate.setText(String.format("Dự kiến: %s", rate.getEstimatedDelivery()));

        // Phí vận chuyển
        if (rate.isFreeShip()) {
            holder.tvRateFee.setText("Miễn phí");
            holder.tvRateFee.setTextColor(context.getResources().getColor(R.color.colorPrimary, context.getTheme())); // Giả sử colorPrimary là màu xanh/cam
        } else {
            holder.tvRateFee.setText(String.format(Locale.GERMAN, "%,.0f đ", rate.getShippingFee()));
            holder.tvRateFee.setTextColor(context.getResources().getColor(R.color.colorBlack, context.getTheme()));
        }

        // Xử lý Radio button
        holder.radioButton.setChecked(position == selectedPosition);

        // ⭐ SỬA LỖI WARNING: Bắt sự kiện click
        holder.itemView.setOnClickListener(v -> {
            int currentPosition = holder.getAdapterPosition(); // Sử dụng getAdapterPosition() cho an toàn
            if (currentPosition != RecyclerView.NO_POSITION && currentPosition != selectedPosition) {
                notifyItemChanged(selectedPosition); // Cập nhật mục cũ
                selectedPosition = currentPosition;
                notifyItemChanged(selectedPosition); // Cập nhật mục mới
                listener.onRateSelected(rate);
            }
        });

        holder.radioButton.setOnClickListener(v -> holder.itemView.performClick());
    }

    @Override
    public int getItemCount() {
        return rates.size();
    }

    // ⭐ MỚI: Thêm phương thức để set vị trí được chọn từ bên ngoài (Dùng cho tự động chọn)
    public void setSelectedPosition(int position) {
        if (position >= 0 && position < rates.size()) {
            if (selectedPosition != RecyclerView.NO_POSITION) {
                notifyItemChanged(selectedPosition); // Cập nhật mục cũ
            }
            selectedPosition = position;
            notifyItemChanged(selectedPosition); // Cập nhật mục mới
        }
    }


    public static class RateViewHolder extends RecyclerView.ViewHolder {
        RadioButton radioButton;
        TextView tvRateName;
        TextView tvEstimate;
        TextView tvRateFee;

        public RateViewHolder(@NonNull View itemView) {
            super(itemView);
            // Giả định các ID này tồn tại trong item_shipping_rate.xml
            radioButton = itemView.findViewById(R.id.rb_rate_select);
            tvRateName = itemView.findViewById(R.id.tv_rate_name);
            tvEstimate = itemView.findViewById(R.id.tv_rate_estimate);
            tvRateFee = itemView.findViewById(R.id.tv_rate_fee);
        }
    }
}