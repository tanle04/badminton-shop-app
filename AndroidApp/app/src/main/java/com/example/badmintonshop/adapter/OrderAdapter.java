package com.example.badmintonshop.adapter;

import android.content.Context;
import android.graphics.Color;
import android.util.Log; // ⭐ Import Log for debugging image URLs if needed
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

    // 1. INTERFACE LISTENER (includes onListRepayClicked)
    public interface OrderAdapterListener {
        void onReviewClicked(int orderId);
        void onRefundClicked(int orderId);
        void onTrackClicked(int orderId);
        void onBuyAgainClicked(int orderId);
        void onOrderClicked(OrderDto order);
        void onListRepayClicked(int orderId); // Listener for the repay button in the list
    }

    private final Context context;
    private List<OrderDto> orderList;
    private final OrderAdapterListener listener;
    // ⭐ Double-check this URL. Use your PC's internal IP if testing on a real device.
    private static final String BASE_IMAGE_URL = "http://10.0.2.2/api/BadmintonShop/images/";

    // Constructor (Correct)
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

        // 1. Update order header info (Correct)
        holder.tvOrderId.setText(String.format("Order #%d", order.getOrderID()));
        // Use the improved status display name
        holder.tvStatus.setText(getStatusDisplayName(order.getStatus(), order.getPaymentMethod(), order.getPaymentStatus()));
        holder.tvStatus.setTextColor(getStatusColor(order.getStatus())); // Color might need adjustment based on payment status too
        holder.tvDate.setText(formatDate(order.getOrderDate()));
        holder.tvTotal.setText(String.format(Locale.GERMAN, "%,.0f đ", order.getTotal()));

        // Calculate total quantity (Correct)
        int totalQuantity = 0;
        if (items != null) {
            for (OrderDetailDto item : items) {
                totalQuantity += item.getQuantity();
            }
        }
        holder.tvItemCount.setText(String.format(Locale.getDefault(), " (%d items)", totalQuantity));

        // 2. Display product images (Correct logic, check URL/Glide issues for missing images)
        displayOrderItems(holder.llItemPreviews, items);

        // 3. Setup action buttons (Includes repay button logic)
        setupActionButtons(holder, order);

        // 4. Handle click on the entire order item (Correct)
        holder.itemView.setOnClickListener(v -> {
            if (listener != null) {
                listener.onOrderClicked(order);
            }
        });
    }

    // (getItemCount, updateData - Correct)
    @Override
    public int getItemCount() { return orderList != null ? orderList.size() : 0; }
    public void updateData(List<OrderDto> newOrders) { this.orderList = newOrders; notifyDataSetChanged(); }

    // --- PRIVATE HELPER METHODS ---

    // Check if all items in the order have been reviewed
    private boolean isOrderFullyReviewed(List<OrderDetailDto> items) {
        if (items == null || items.isEmpty()) {
            // Consider if an empty order should be "reviewed" - probably not relevant for buttons
            return false;
        }
        for (OrderDetailDto item : items) {
            // Assuming isReviewed() correctly reflects the review status from your DTO
            if (!item.isReviewed()) {
                return false; // Found one item not reviewed
            }
        }
        return true; // All items are reviewed
    }

    // Display item preview images
    private void displayOrderItems(LinearLayout container, List<OrderDetailDto> items) {
        container.removeAllViews(); // Clear previous images
        int maxDisplay = 4; // Show up to 4 images

        if (items == null || context == null) return; // Need context for LayoutInflater and Glide

        for (int i = 0; i < items.size() && i < maxDisplay; i++) {
            OrderDetailDto item = items.get(i);
            // Inflate the layout which is likely just an ImageView
            View previewView = LayoutInflater.from(context).inflate(R.layout.include_order_item_preview, container, false);
            ImageView imageView; // Declare the ImageView variable

            // ⭐ FIX: Check if the inflated view itself IS an ImageView
            if (previewView instanceof ImageView) {
                imageView = (ImageView) previewView; // Cast the inflated view directly
            } else {
                // ⭐ Fallback: If the layout is complex, try finding by ID (Ensure ID exists in XML)
                // imageView = previewView.findViewById(R.id.your_image_view_id_in_include_layout); // Replace with actual ID if needed
                // if (imageView == null) {
                Log.e("OrderAdapter", "ImageView not found in include_order_item_preview layout. Ensure the layout root is an ImageView or it contains an ImageView with a correct ID.");
                continue; // Skip if ImageView is missing
                // }
            }

            String imageUrl = BASE_IMAGE_URL + item.getImageUrl();
            Log.d("IMAGE_DEBUG", "Loading image in adapter: " + imageUrl); // Debug URL

            Glide.with(context)
                    .load(imageUrl)
                    .placeholder(R.drawable.ic_badminton_logo) // Placeholder while loading
                    .error(R.drawable.ic_badminton_logo)      // Image shown on error
                    .into(imageView); // Load image into the found/casted ImageView

            container.addView(previewView); // Add the inflated view (which contains the ImageView)
        }
    }

    // Setup visibility and actions for buttons based on order status (Correct Logic)
    private void setupActionButtons(OrderViewHolder holder, OrderDto order) {
        String paymentMethod = order.getPaymentMethod();
        String status = order.getStatus();
        String paymentStatus = order.getPaymentStatus();
        List<OrderDetailDto> items = order.getItems();

        // Hide all buttons initially
        holder.btnListRepay.setVisibility(View.GONE);
        holder.btnTrack.setVisibility(View.GONE);
        holder.btnReview.setVisibility(View.GONE);
        holder.btnReturnRefund.setVisibility(View.GONE);

        // --- Logic to show buttons based on status ---

        // 1. Priority: Repay? (Only VNPay Pending and not Paid)
        boolean canRepay = "VNPay".equals(paymentMethod) && "Pending".equals(status) &&
                (paymentStatus == null || "".equals(paymentStatus) || "Pending".equals(paymentStatus) || "Unpaid".equals(paymentStatus) || "Failed".equals(paymentStatus));

        if (canRepay) {
            holder.btnListRepay.setVisibility(View.VISIBLE);
            holder.btnListRepay.setOnClickListener(v -> {
                if (listener != null) listener.onListRepayClicked(order.getOrderID());
            });
            // Don't show other buttons when awaiting payment
            return; // Stop here
        }

        // 2. Track? (Processing or Shipped)
        if ("Processing".equals(status) || "Shipped".equals(status)) {
            holder.btnTrack.setVisibility(View.VISIBLE);
            holder.btnTrack.setOnClickListener(v -> {
                if (listener != null) listener.onTrackClicked(order.getOrderID());
            });
            // Decide if you want "Buy Again" here too. If not, return.
            // return;
        }

        // 3. Delivered?
        if ("Delivered".equals(status)) {
            // Show Return/Refund unconditionally for delivered orders? Adjust as needed.
            holder.btnReturnRefund.setVisibility(View.VISIBLE);
            holder.btnReturnRefund.setOnClickListener(v -> {
                if (listener != null) listener.onRefundClicked(order.getOrderID());
            });

            boolean fullyReviewed = isOrderFullyReviewed(items);
            if (fullyReviewed) {
                // All items reviewed -> Show "Buy Again"
                holder.btnReview.setVisibility(View.VISIBLE);
                holder.btnReview.setText("Mua lại");
                holder.btnReview.setTextColor(ContextCompat.getColor(context, R.color.orange_800)); // Or your desired color
                holder.btnReview.setOnClickListener(v -> {
                    if (listener != null) listener.onBuyAgainClicked(order.getOrderID());
                });
            } else {
                // Not all items reviewed -> Show "Leave a review"
                holder.btnReview.setVisibility(View.VISIBLE);
                holder.btnReview.setText("Leave a review");
                holder.btnReview.setTextColor(ContextCompat.getColor(context, R.color.red_700)); // Or your desired color
                holder.btnReview.setOnClickListener(v -> {
                    if (listener != null) listener.onReviewClicked(order.getOrderID());
                });
            }
            return; // Stop here for Delivered status
        }

        // 4. Cancelled / Refunded?
        if ("Cancelled".equals(status) || "Refunded".equals(status)) {
            // Only show "Buy Again"
            holder.btnReview.setVisibility(View.VISIBLE); // Re-using the review button slot
            holder.btnReview.setText("Mua lại");
            holder.btnReview.setTextColor(ContextCompat.getColor(context, R.color.orange_800)); // Or your desired color
            holder.btnReview.setOnClickListener(v -> {
                if (listener != null) listener.onBuyAgainClicked(order.getOrderID());
            });
            // No other buttons needed
            return; // Stop here
        }

        // Other statuses (like initial Pending for COD) might not show any buttons based on this logic.
    }

    // Get status color (Correct)
    private int getStatusColor(String status) {
        if (status == null) return Color.GRAY;
        switch (status) {
            case "Delivered": return ContextCompat.getColor(context, R.color.green_700);
            case "Processing":
            case "Shipped": return ContextCompat.getColor(context, R.color.orange_800);
            case "Cancelled":
            case "Refunded": return ContextCompat.getColor(context, R.color.red_700);
            case "Pending": // Pending might need differentiation (COD vs VNPay unpaid)
            default: return Color.GRAY; // Default for Pending or unknown
        }
    }

    // Get display name for status (Improved)
    private String getStatusDisplayName(String status, String paymentMethod, String paymentStatus) {
        if (status == null) return "Unknown";
        // Specific case for unpaid VNPay
        if ("Pending".equals(status) && "VNPay".equals(paymentMethod) && !"Paid".equals(paymentStatus)) {
            return "Chờ thanh toán";
        }
        // General cases
        switch (status) {
            case "Pending": return "Chờ xác nhận";
            case "Processing": return "Đang xử lý";
            case "Shipped": return "Đang vận chuyển";
            case "Delivered": return "Đã giao hàng";
            case "Cancelled": return "Đã hủy";
            case "Refunded": return "Đã hoàn tiền";
            default: return status; // Return the raw status if unknown
        }
    }

    // Format date string (Correct)
    private String formatDate(String isoDate) {
        if (isoDate == null || isoDate.length() < 10) return "N/A";
        // Takes yyyy-MM-dd part
        return isoDate.substring(0, 10).replace('-', '/');
    }

    // --- VIEWHOLDER CLASS (Correct - includes btnListRepay) ---
    public static class OrderViewHolder extends RecyclerView.ViewHolder {
        TextView tvOrderId, tvStatus, tvDate, tvTotal, tvItemCount;
        LinearLayout llItemPreviews;
        // Buttons for actions
        Button btnListRepay, btnReview, btnReturnRefund, btnTrack;

        public OrderViewHolder(@NonNull View itemView) {
            super(itemView);
            tvOrderId = itemView.findViewById(R.id.tv_order_id);
            tvStatus = itemView.findViewById(R.id.tv_order_status);
            tvDate = itemView.findViewById(R.id.tv_order_date);
            tvTotal = itemView.findViewById(R.id.tv_order_total);
            llItemPreviews = itemView.findViewById(R.id.ll_item_previews);
            tvItemCount = itemView.findViewById(R.id.tv_item_count);

            // Find all buttons by their IDs from item_order_summary.xml
            btnListRepay = itemView.findViewById(R.id.btn_list_repay);
            btnReview = itemView.findViewById(R.id.btn_review);
            btnReturnRefund = itemView.findViewById(R.id.btn_return_refund);
            btnTrack = itemView.findViewById(R.id.btn_track);
        }
    }
}