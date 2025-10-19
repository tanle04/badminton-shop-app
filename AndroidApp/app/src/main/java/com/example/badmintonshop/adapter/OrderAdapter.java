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
import android.widget.Toast;

import androidx.annotation.NonNull;
import androidx.core.content.ContextCompat;
import androidx.recyclerview.widget.RecyclerView;

import com.bumptech.glide.Glide;
import com.example.badmintonshop.R;
import com.example.badmintonshop.network.ApiClient;
import com.example.badmintonshop.network.dto.OrderDto;
import com.example.badmintonshop.network.dto.OrderDetailDto;

import java.util.List;
import java.util.Locale;

public class OrderAdapter extends RecyclerView.Adapter<OrderAdapter.OrderViewHolder> {

    // 1. Định nghĩa Interface Listener (cho các hành động)
    public interface OrderAdapterListener {
        void onReviewClicked(int orderId);
        void onRefundClicked(int orderId);
        void onTrackClicked(int orderId);
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

        // ⭐ SỬA LỖI: Tính và hiển thị số lượng mục (Total Item Count)
        int totalItemsInOrder = items != null ? items.size() : 0; // Lấy số lượng loại sản phẩm
        // Tuy nhiên, để tính tổng số lượng *sản phẩm* (ví dụ: 2 vợt + 3 giày = 5 items),
        // ta cần dùng getTotalItemsCount() (giả sử đã được thêm vào OrderDto)
        // HOẶC tính tổng quantity trong adapter:
        int totalQuantity = 0;
        if (items != null) {
            for (OrderDetailDto item : items) {
                totalQuantity += item.getQuantity();
            }
        }

        holder.tvItemCount.setText(String.format(Locale.getDefault(), " (%d items)", totalQuantity));


        // 2. Hiển thị danh sách sản phẩm (tối đa 4 ảnh)
        // ⭐ SỬA LỖI: Bỏ tham số thứ ba (totalCount)
        displayOrderItems(holder.llItemPreviews, items);

        // 3. Xử lý các nút hành động
        setupActionButtons(holder, order);
    }

    @Override
    public int getItemCount() {
        return orderList != null ? orderList.size() : 0;
    }

    // Cập nhật danh sách (dùng trong OrderFragment)
    public void updateData(List<OrderDto> newOrders) {
        this.orderList = newOrders;
        notifyDataSetChanged();
    }

    // --- PRIVATE HELPER METHODS ---

    // ⭐ SỬA LỖI: Loại bỏ tham số totalCount
    private void displayOrderItems(LinearLayout container, List<OrderDetailDto> items) {
        container.removeAllViews();
        int maxDisplay = 4;

        if (items == null) return;

        // Hiển thị tối đa maxDisplay ảnh sản phẩm
        for (int i = 0; i < items.size() && i < maxDisplay; i++) {
            OrderDetailDto item = items.get(i);
            // ⭐ SỬA: Phải inflate layout chứa ImageView tóm tắt (include_order_item_preview.xml)
            // Thay vì cố gắng cast View sang ImageView, ta inflate nó an toàn hơn.
            View previewView = LayoutInflater.from(context).inflate(R.layout.include_order_item_preview, container, false);
            ImageView imageView = (ImageView) previewView; // Giả định include_order_item_preview là một ImageView

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

        // Logic hiển thị nút dựa trên trạng thái
        if (status.equals("Processing") || status.equals("Shipped")) {
            holder.btnTrack.setVisibility(View.VISIBLE);
        } else if (status.equals("Delivered")) {
            holder.btnReview.setVisibility(View.VISIBLE);
            holder.btnReturnRefund.setVisibility(View.VISIBLE);
        }

        // Gán sự kiện click
        if (listener != null) {
            holder.btnReview.setOnClickListener(v -> listener.onReviewClicked(order.getOrderID()));
            holder.btnReturnRefund.setOnClickListener(v -> listener.onRefundClicked(order.getOrderID()));
            holder.btnTrack.setOnClickListener(v -> listener.onTrackClicked(order.getOrderID()));
        }
    }

    private int getStatusColor(String status) {
        // ⭐ LƯU Ý: Phải đảm bảo các màu này đã được định nghĩa trong colors.xml
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
        // Tùy biến: Giả sử orderDate là ISO string (2025-10-17 17:34:34)
        if (isoDate == null || isoDate.length() < 10) return "N/A";
        // Lấy ngày tháng (ví dụ: "17 Oct 2025")
        return isoDate.substring(0, 10).replace('-', '/');
    }

    // --- VIEWHOLDER CLASS ---

    public static class OrderViewHolder extends RecyclerView.ViewHolder {
        TextView tvOrderId, tvStatus, tvDate, tvTotal, tvItemCount;
        LinearLayout llItemPreviews;
        Button btnReview, btnReturnRefund, btnTrack;

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