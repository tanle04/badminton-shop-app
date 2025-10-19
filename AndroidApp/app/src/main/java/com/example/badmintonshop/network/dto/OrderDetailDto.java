package com.example.badmintonshop.network.dto;

import com.google.gson.annotations.SerializedName;
import java.io.Serializable;

public class OrderDetailDto implements Serializable {

    @SerializedName("orderDetailID")
    private int orderDetailID;

    @SerializedName("productName")
    private String productName;

    @SerializedName("imageUrl")
    private String imageUrl; // Ảnh sản phẩm

    @SerializedName("quantity")
    private int quantity;

    // Getters and Setters...

    public int getOrderDetailID() {
        return orderDetailID;
    }

    public void setOrderDetailID(int orderDetailID) {
        this.orderDetailID = orderDetailID;
    }

    public String getProductName() {
        return productName;
    }

    public void setProductName(String productName) {
        this.productName = productName;
    }

    public String getImageUrl() {
        return imageUrl;
    }

    public void setImageUrl(String imageUrl) {
        this.imageUrl = imageUrl;
    }

    public int getQuantity() {
        return quantity;
    }

    public void setQuantity(int quantity) {
        this.quantity = quantity;
    }
}