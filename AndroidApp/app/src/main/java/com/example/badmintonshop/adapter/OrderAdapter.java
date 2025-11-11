// File: app/src/main/java/com/example/badmintonshop/adapter/OrderAdapter.java
// NỘI DUNG ĐÃ SỬA

package com.example.badmintonshop.adapter;

import android.content.Context;
import android.graphics.Color;
import android.util.Log;
import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.widget.Button;
import android.widget.ImageView;
import android.widget.LinearLayout;
import android.widget.TextView;

import androidx.annotation.NonNull;
import androidx.core.content.ContextCompat;
import androidx.recyclerview.widget.RecyclerView;

import com.bumptech.glide.Glide;
import com.example.badmintonshop.R;
import com.example.badmintonshop.network.dto.OrderDto;
import com.example.badmintonshop.network.dto.OrderDetailDto;

import java.util.List;
import java.util.Locale;

public class OrderAdapter extends RecyclerView.Adapter<OrderAdapter.OrderViewHolder> {

    // 1. INTERFACE LISTENER (Đã có onTrackClicked)
    public interface OrderAdapterListener {
        void onReviewClicked(int orderId);
        void onRefundClicked(int orderId);
        void onTrackClicked(int orderId);
        void onBuyAgainClicked(int orderId);
        void onOrderClicked(OrderDto order);
        void onListRepayClicked(int orderId);
    }

    private final Context context;
    private List<OrderDto> orderList;
    private final OrderAdapterListener listener;

    public OrderAdapter(Context context, List<OrderDto> orderList, OrderAdapterListener listener) {
        this.context = context;
        this.orderList = orderList;
        this.listener = listener;
    }

    @NonNull
    @Override
    public OrderViewHolder onCreateViewHolder(@NonNull ViewGroup parent, int viewType) {
        View view = LayoutInflater.from(context).inflate(R.layout.item_order_summary, parent, false);
        return new OrderViewHolder(view);
    }

    @Override
    public void onBindViewHolder(@NonNull OrderViewHolder holder, int position) {
        OrderDto order = orderList.get(position);
        List<OrderDetailDto> items = order.getItems();

        // 1. Thông tin header
        holder.tvOrderId.setText(String.format("Order #%d", order.getOrderID()));
        holder.tvStatus.setText(getStatusDisplayName(order.getStatus(), order.getPaymentMethod(), order.getPaymentStatus()));
        holder.tvStatus.setTextColor(getStatusColor(order.getStatus()));
        holder.tvDate.setText(formatDate(order.getOrderDate()));
        holder.tvTotal.setText(String.format(Locale.GERMAN, "%,.0f đ", order.getTotal()));

        // 2. Đếm số lượng
        int totalQuantity = 0;
        if (items != null) {
            for (OrderDetailDto item : items) {
                totalQuantity += item.getQuantity();
            }
        }
        holder.tvItemCount.setText(String.format(Locale.getDefault(), " (%d items)", totalQuantity));

        // 3. Hiển thị ảnh
        displayOrderItems(holder.llItemPreviews, items);

        // 4. Cài đặt các nút action
        setupActionButtons(holder, order);

        // 5. Click vào cả item (Mở OrderDetailActivity)
        holder.itemView.setOnClickListener(v -> {
            if (listener != null) {
                listener.onOrderClicked(order);
            }
        });
    }

    @Override
    public int getItemCount() { return orderList != null ? orderList.size() : 0; }
    public void updateData(List<OrderDto> newOrders) { this.orderList = newOrders; notifyDataSetChanged(); }

    // --- CÁC HÀM HỖ TRỢ ---

    private boolean isOrderFullyReviewed(List<OrderDetailDto> items) {
        if (items == null || items.isEmpty()) return false;
        for (OrderDetailDto item : items) {
            if (!item.isReviewed()) {
                return false;
            }
        }
        return true;
    }

    private void displayOrderItems(LinearLayout container, List<OrderDetailDto> items) {
        container.removeAllViews();
        int maxDisplay = 4;

        if (items == null || context == null) return;

        for (int i = 0; i < items.size() && i < maxDisplay; i++) {
            OrderDetailDto item = items.get(i);
            View previewView = LayoutInflater.from(context).inflate(R.layout.include_order_item_preview, container, false);

            // Tìm ImageView bằng ID bên trong previewView
            ImageView imageView = previewView.findViewById(R.id.iv_order_item_preview);

            if (imageView == null) {
                Log.e("OrderAdapter", "LỖI: Không tìm thấy R.id.iv_order_item_preview trong layout include_order_item_preview.xml");
                continue;
            }

            String imageUrl = item.getImageUrl();
            Glide.with(context)
                    .load(imageUrl)
                    .placeholder(R.drawable.ic_badminton_logo)
                    .error(R.drawable.ic_badminton_logo)
                    .into(imageView);

            container.addView(previewView);
        }
    }

    /**
     * ⭐ HÀM ĐÃ SỬA LOGIC HIỂN THỊ NÚT "TRACK" ⭐
     */
    private void setupActionButtons(OrderViewHolder holder, OrderDto order) {
        String paymentMethod = order.getPaymentMethod();
        String status = order.getStatus();
        String paymentStatus = order.getPaymentStatus();
        List<OrderDetailDto> items = order.getItems();

        // Ẩn tất cả các nút
        holder.btnListRepay.setVisibility(View.GONE);
        holder.btnTrack.setVisibility(View.GONE);
        holder.btnReview.setVisibility(View.GONE);
        holder.btnReturnRefund.setVisibility(View.GONE);

        // --- Logic mới ---

        // 1. Ưu tiên: Chờ thanh toán?
        boolean canRepay = "VNPay".equals(paymentMethod) && "Pending".equals(status) &&
                (paymentStatus == null || "".equals(paymentStatus) || "Pending".equals(paymentStatus) || "Unpaid".equals(paymentStatus) || "Failed".equals(paymentStatus));

        if (canRepay) {
            holder.btnListRepay.setVisibility(View.VISIBLE);
            holder.btnListRepay.setOnClickListener(v -> {
                if (listener != null) listener.onListRepayClicked(order.getOrderID());
            });
            return; // Chỉ hiển thị nút "Thanh toán lại", không hiển thị các nút khác
        }

        // 2. Hiển thị nút "Theo dõi" cho TẤT CẢ các trạng thái khác
        // (Processing, Shipped, Delivered, Cancelled, Refunded, v.v.)
        holder.btnTrack.setVisibility(View.VISIBLE);
        holder.btnTrack.setOnClickListener(v -> {
            if (listener != null) {
                // Gọi đúng listener onTrackClicked
                listener.onTrackClicked(order.getOrderID());
            }
        });

        // 3. Hiển thị các nút BỔ SUNG tùy theo trạng thái

        if ("Delivered".equals(status)) {
            // Đã giao: Hiển thị "Trả hàng"
            holder.btnReturnRefund.setVisibility(View.VISIBLE);
            holder.btnReturnRefund.setOnClickListener(v -> {
                if (listener != null) listener.onRefundClicked(order.getOrderID());
            });

            boolean fullyReviewed = isOrderFullyReviewed(items);
            if (fullyReviewed) {
                // Đã review hết: Hiển thị "Mua lại"
                holder.btnReview.setVisibility(View.VISIBLE);
                holder.btnReview.setText("Mua lại");
                holder.btnReview.setTextColor(ContextCompat.getColor(context, R.color.orange_800)); // Cần định nghĩa
                holder.btnReview.setOnClickListener(v -> {
                    if (listener != null) listener.onBuyAgainClicked(order.getOrderID());
                });
            } else {
                // Chưa review: Hiển thị "Đánh giá"
                holder.btnReview.setVisibility(View.VISIBLE);
                holder.btnReview.setText("Leave a review");
                holder.btnReview.setTextColor(ContextCompat.getColor(context, R.color.red_700)); // Cần định nghĩa
                holder.btnReview.setOnClickListener(v -> {
                    if (listener != null) listener.onReviewClicked(order.getOrderID());
                });
            }
        }
        else if ("Cancelled".equals(status) || "Refunded".equals(status) || "Refund Requested".equals(status)) {
            // Đã hủy/Hoàn tiền: Chỉ hiển thị "Mua lại" (dùng chung slot với nút review)
            holder.btnReview.setVisibility(View.VISIBLE);
            holder.btnReview.setText("Mua lại");
            holder.btnReview.setTextColor(ContextCompat.getColor(context, R.color.orange_800)); // Cần định nghĩa
            holder.btnReview.setOnClickListener(v -> {
                if (listener != null) listener.onBuyAgainClicked(order.getOrderID());
            });
        }
        // Các trạng thái "Processing" và "Shipped" sẽ chỉ hiển thị nút "Track" (đã xử lý ở bước 2).
    }


    private int getStatusColor(String status) {
        if (status == null) return Color.GRAY;
        switch (status) {
            case "Delivered": return ContextCompat.getColor(context, R.color.success_color);
            case "Processing":
            case "Shipped": return ContextCompat.getColor(context, R.color.pending_color);
            case "Cancelled":
            case "Refunded":
            case "Refund Requested": // Thêm trạng thái này
                return ContextCompat.getColor(context, R.color.error_color);
            case "Pending":
            default: return Color.GRAY;
        }
    }

    private String getStatusDisplayName(String status, String paymentMethod, String paymentStatus) {
        if (status == null) return "Unknown";

        if ("Pending".equals(status) && "VNPay".equals(paymentMethod) && !"Paid".equals(paymentStatus)) {
            return "Chờ thanh toán";
        }

        switch (status) {
            case "Pending": return "Chờ xác nhận";
            case "Processing": return "Đang xử lý";
            case "Shipped": return "Đang vận chuyển";
            case "Delivered": return "Đã giao hàng";
            case "Cancelled": return "Đã hủy";
            case "Refunded": return "Đã hoàn tiền";
            case "Refund Requested": return "Yêu cầu hoàn tiền";
            default: return status;
        }
    }

    private String formatDate(String isoDate) {
        if (isoDate == null || isoDate.length() < 10) return "N/A";
        return isoDate.substring(0, 10).replace('-', '/');
    }

    // --- VIEWHOLDER CLASS ---
    public static class OrderViewHolder extends RecyclerView.ViewHolder {
        TextView tvOrderId, tvStatus, tvDate, tvTotal, tvItemCount;
        LinearLayout llItemPreviews;
        Button btnListRepay, btnReview, btnReturnRefund, btnTrack;

        public OrderViewHolder(@NonNull View itemView) {
            super(itemView);
            tvOrderId = itemView.findViewById(R.id.tv_order_id);
            tvStatus = itemView.findViewById(R.id.tv_order_status);
            tvDate = itemView.findViewById(R.id.tv_order_date);
            tvTotal = itemView.findViewById(R.id.tv_order_total);
            llItemPreviews = itemView.findViewById(R.id.ll_item_previews);
            tvItemCount = itemView.findViewById(R.id.tv_item_count);

            btnListRepay = itemView.findViewById(R.id.btn_list_repay);
            btnReview = itemView.findViewById(R.id.btn_review);
            btnReturnRefund = itemView.findViewById(R.id.btn_return_refund);
            btnTrack = itemView.findViewById(R.id.btn_track);
        }
    }
}