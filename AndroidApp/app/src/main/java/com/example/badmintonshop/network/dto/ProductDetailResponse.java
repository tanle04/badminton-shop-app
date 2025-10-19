package com.example.badmintonshop.network.dto;

import com.google.gson.annotations.SerializedName;

public class ProductDetailResponse {

    // Trường trạng thái API chung
    @SerializedName("isSuccess")
    private boolean isSuccess;

    @SerializedName("message")
    private String message;

    // Trường dữ liệu sản phẩm chi tiết
    @SerializedName("product") // HOẶC tên key mà API dùng để chứa ProductDto
    private ProductDto product;

    // Getters
    public boolean isSuccess() {
        return isSuccess;
    }

    public String getMessage() {
        return message;
    }

    public ProductDto getProduct() {
        return product;
    }
}