package com.example.badmintonshop.network.dto;

// File: CartResponse.java
import com.example.badmintonshop.model.CartItem;
import com.google.gson.annotations.SerializedName;

import java.util.List;

public class CartResponse {

    // ⭐ CORRECTION: Use @SerializedName to map to 'isSuccess' or the API's actual success field name.
    // Assuming your API uses 'isSuccess' consistently.
    @SerializedName("isSuccess")
    private boolean isSuccess;

    @SerializedName("message")
    private String message;

    @SerializedName("items")
    private List<CartItem> items;

    // Getters
    public boolean isSuccess() {
        return isSuccess;
    }

    public String getMessage() {
        return message;
    }

    public List<CartItem> getItems() {
        return items;
    }
}