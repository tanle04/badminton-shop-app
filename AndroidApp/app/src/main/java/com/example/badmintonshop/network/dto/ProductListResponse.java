package com.example.badmintonshop.network.dto;

import com.google.gson.annotations.SerializedName;
import java.util.List;

public class ProductListResponse {

    // ⭐ THÊM: Các trường isSuccess và message để nhất quán với định dạng phản hồi API
    @SerializedName("isSuccess")
    private boolean isSuccess;

    @SerializedName("message")
    private String message;

    @SerializedName("page")
    private int page;

    @SerializedName("items")
    private List<ProductDto> items;

    // --- Getters MỚI ---
    public boolean isSuccess() {
        return isSuccess;
    }

    public String getMessage() {
        return message;
    }

    // --- Getters CŨ ---
    public int getPage() {
        return page;
    }

    public List<ProductDto> getItems() {
        return items;
    }

    @Override
    public String toString() {
        return "ProductListResponse{" +
                "isSuccess=" + isSuccess +
                ", message='" + message + '\'' +
                ", page=" + page +
                ", items=" + (items != null ? items.size() : 0) +
                '}';
    }
}