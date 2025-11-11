package com.example.badmintonshop.network.dto;

import com.google.gson.annotations.SerializedName;
import java.util.List;

public class RefundRequestBody {
    @SerializedName("customerID")
    private int customerID;
    @SerializedName("orderID")
    private int orderID;
    @SerializedName("reason")
    private String reason;
    @SerializedName("items")
    private List<RefundItem> items;

    // Constructor
    public RefundRequestBody(int customerID, int orderID, String reason, List<RefundItem> items) {
        this.customerID = customerID;
        this.orderID = orderID;
        this.reason = reason;
        this.items = items;
    }
    // Inner class for items
    public static class RefundItem {
        @SerializedName("orderDetailID")
        private int orderDetailID;
        @SerializedName("quantity")
        private int quantity;
        @SerializedName("reason")
        private String reason;

        public RefundItem(int orderDetailID, int quantity, String reason) {
            this.orderDetailID = orderDetailID;
            this.quantity = quantity;
            this.reason = reason;
        }
    }
}