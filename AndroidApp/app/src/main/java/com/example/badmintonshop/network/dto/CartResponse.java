package com.example.badmintonshop.network.dto;

// File: CartResponse.java
import com.example.badmintonshop.model.CartItem;

import java.util.List;

public class CartResponse {
    private boolean success;
    private List<CartItem> items;

    // Getters
    public boolean isSuccess() { return success; }
    public List<CartItem> getItems() { return items; }
}