package com.example.badmintonshop.network.dto;

import com.google.gson.annotations.SerializedName;
import java.io.Serializable;
import java.util.List;

public class OrderDto implements Serializable {

    @SerializedName("orderID")
    private int orderID;

    @SerializedName("orderDate")
    private String orderDate;

    @SerializedName("status")
    private String status; // Pending, Processing, Shipped, Delivered, Cancelled, Refunded

    @SerializedName("total")
    private double total;

    @SerializedName("paymentMethod")
    private String paymentMethod;

    // ⭐ ITEMS: Danh sách các sản phẩm (OrderDetails)
    @SerializedName("items")
    private List<OrderDetailDto> items;

    // Getters and Setters... (cần thiết cho Gson)
    public int getOrderID() { return orderID; }
    public String getOrderDate() { return orderDate; }
    public String getStatus() { return status; }
    public double getTotal() { return total; }
    public String getPaymentMethod() { return paymentMethod; }
    public List<OrderDetailDto> getItems() { return items; }
    public int getTotalItemsCount() {
        int count = 0;
        if (items != null) {
            for (OrderDetailDto item : items) {
                // Assuming OrderDetailDto has a getQuantity() method
                count += item.getQuantity();
            }
        }
        return count;
    }
}