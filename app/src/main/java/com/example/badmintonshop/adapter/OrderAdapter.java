package com.example.badmintonshop.adapter;

import android.content.Context;
import android.graphics.Color;
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

    // 1. ĐỊNH NGHĨA INTERFACE LISTENER
    public interface OrderAdapterListener {
        void onReviewClicked(int orderId);
        void onRefundClicked(int orderId);
        void onTrackClicked(int orderId);
        // Chỉ truyền OrderID
        void onBuyAgainClicked(int orderId);
    }

    private final Context context;
    private List<OrderDto> orderList;
    private final OrderAdapterListener listener;
    private static final String BASE_IMAGE_URL = "http://10.0.2.2/api/BadmintonShop/images/uploads/";

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

        // 1. Cập nhật thông tin tiêu đề đơn hàng
        holder.tvOrderId.setText(String.format("Order #%d", order.getOrderID()));
        holder.tvStatus.setText(getStatusDisplayName(order.getStatus()));
        holder.tvStatus.setTextColor(getStatusColor(order.getStatus()));
        holder.tvDate.setText(formatDate(order.getOrderDate()));
        holder.tvTotal.setText(String.format(Locale.GERMAN, "%,.0f đ", order.getTotal()));

        // Tính tổng số lượng sản phẩm
        int totalQuantity = 0;
        if (items != null) {
            for (OrderDetailDto item : items) {
                totalQuantity += item.getQuantity();
            }
        }
        holder.tvItemCount.setText(String.format(Locale.getDefault(), " (%d items)", totalQuantity));


        // 2. Hiển thị danh sách sản phẩm (tối đa 4 ảnh)
        displayOrderItems(holder.llItemPreviews, items);

        // ⭐ 3. XỬ LÝ NÚT HÀNH ĐỘNG
        setupActionButtons(holder, order);
    }

    @Override
    public int getItemCount() {
        return orderList != null ? orderList.size() : 0;
    }

    public void updateData(List<OrderDto> newOrders) {
        this.orderList = newOrders;
        notifyDataSetChanged();
    }

    // --- PRIVATE HELPER METHODS ---

    // HÀM HELPER: KIỂM TRA TẤT CẢ SẢN PHẨM TRONG ĐƠN HÀNG ĐÃ ĐƯỢC ĐÁNH GIÁ CHƯA
    private boolean isOrderFullyReviewed(List<OrderDetailDto> items) {
        if (items == null || items.isEmpty()) {
            return false;
        }
        for (OrderDetailDto item : items) {
            if (!item.isReviewed()) {
                return false; // Chỉ cần 1 mục chưa đánh giá là coi như CHƯA xong
            }
        }
        return true; // Tất cả mục đều đã được đánh giá
    }

    private void displayOrderItems(LinearLayout container, List<OrderDetailDto> items) {
        container.removeAllViews();
        int maxDisplay = 4;

        if (items == null) return;

        for (int i = 0; i < items.size() && i < maxDisplay; i++) {
            OrderDetailDto item = items.get(i);
            View previewView = LayoutInflater.from(context).inflate(R.layout.include_order_item_preview, container, false);
            ImageView imageView = (ImageView) previewView;

            String imageUrl = BASE_IMAGE_URL + item.getImageUrl();
            Glide.with(context)
                    .load(imageUrl)
                    .placeholder(R.drawable.ic_badminton_logo)
                    .error(R.drawable.ic_badminton_logo)
                    .into(imageView);

            container.addView(imageView);
        }
    }

    private void setupActionButtons(OrderViewHolder holder, OrderDto order) {
        String status = order.getStatus();
        holder.btnTrack.setVisibility(View.GONE);
        holder.btnReview.setVisibility(View.GONE);
        holder.btnReturnRefund.setVisibility(View.GONE);

        List<OrderDetailDto> items = order.getItems();
        OrderDetailDto firstDetail = (items != null && !items.isEmpty()) ? items.get(0) : null;

        // Logic hiển thị nút dựa trên trạng thái
        if (status.equals("Processing") || status.equals("Shipped")) {
            holder.btnTrack.setVisibility(View.VISIBLE);
        }

        // ⭐ LOGIC 1: ĐƠN HÀNG ĐÃ GIAO (DELIVERED)
        else if (status.equals("Delivered") && firstDetail != null) {

            holder.btnReturnRefund.setVisibility(View.VISIBLE);
            holder.btnReview.setVisibility(View.VISIBLE);

            boolean fullyReviewed = isOrderFullyReviewed(items);

            if (fullyReviewed) {
                // ĐÃ ĐÁNH GIÁ HẾT -> HIỆN MUA LẠI
                holder.btnReview.setText("Mua lại");
                holder.btnReview.setTextColor(ContextCompat.getColor(context, R.color.orange_800));

                holder.btnReview.setOnClickListener(v -> {
                    if (listener != null) {
                        listener.onBuyAgainClicked(order.getOrderID());
                    }
                });

            } else {
                // CÒN SẢN PHẨM CHƯA ĐÁNH GIÁ -> HIỆN ĐÁNH GIÁ
                holder.btnReview.setText("Leave a review");
                holder.btnReview.setTextColor(ContextCompat.getColor(context, R.color.red_700));

                holder.btnReview.setOnClickListener(v -> {
                    if (listener != null) {
                        listener.onReviewClicked(order.getOrderID());
                    }
                });
            }
        }

        // ⭐ LOGIC 2: ĐƠN HÀNG ĐÃ HỦY HOẶC HOÀN TIỀN
        else if ((status.equals("Cancelled") || status.equals("Refunded")) && firstDetail != null) {

            holder.btnReview.setVisibility(View.VISIBLE); // Chỉ hiện nút Mua lại
            holder.btnReturnRefund.setVisibility(View.GONE); // Ẩn hoàn tiền (vì đã hủy/hoàn)

            holder.btnReview.setText("Mua lại");
            holder.btnReview.setTextColor(ContextCompat.getColor(context, R.color.orange_800));

            holder.btnReview.setOnClickListener(v -> {
                if (listener != null) {
                    listener.onBuyAgainClicked(order.getOrderID());
                }
            });
        }


        // Gán sự kiện cho các nút còn lại
        if (listener != null) {
            holder.btnReturnRefund.setOnClickListener(v -> listener.onRefundClicked(order.getOrderID()));
            holder.btnTrack.setOnClickListener(v -> listener.onTrackClicked(order.getOrderID()));
        }
    }

    private int getStatusColor(String status) {
        switch (status) {
            case "Delivered":
                return ContextCompat.getColor(context, R.color.green_700);
            case "Processing":
            case "Shipped":
                return ContextCompat.getColor(context, R.color.orange_800);
            case "Cancelled":
            case "Refunded":
                return ContextCompat.getColor(context, R.color.red_700);
            default:
                return Color.GRAY;
        }
    }

    private String getStatusDisplayName(String status) {
        switch (status) {
            case "Pending": return "Chờ xác nhận";
            case "Processing": return "Đang xử lý";
            case "Shipped": return "Đang vận chuyển";
            case "Delivered": return "Đã giao hàng";
            case "Cancelled": return "Đã hủy";
            case "Refunded": return "Đã hoàn tiền";
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
        Button btnReview, btnReturnRefund, btnTrack; // Giả định btnReview là nút Leave a review

        public OrderViewHolder(@NonNull View itemView) {
            super(itemView);
            tvOrderId = itemView.findViewById(R.id.tv_order_id);
            tvStatus = itemView.findViewById(R.id.tv_order_status);
            tvDate = itemView.findViewById(R.id.tv_order_date);
            tvTotal = itemView.findViewById(R.id.tv_order_total);
            llItemPreviews = itemView.findViewById(R.id.ll_item_previews);
            tvItemCount = itemView.findViewById(R.id.tv_item_count);

            btnReview = itemView.findViewById(R.id.btn_review);
            btnReturnRefund = itemView.findViewById(R.id.btn_return_refund);
            btnTrack = itemView.findViewById(R.id.btn_track);
        }
    }
}