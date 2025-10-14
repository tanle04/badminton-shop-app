package com.example.badmintonshop.network.dto;

import com.google.gson.annotations.SerializedName;

public class WishlistDeleteRequest {
    @SerializedName("customerID")
    private int customerId;

    @SerializedName("productID")
    private int productId;

    public WishlistDeleteRequest(int customerId, int productId) {
        this.customerId = customerId;
        this.productId = productId;
    }
}
