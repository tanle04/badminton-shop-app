// Tạo file mới: ReviewDetailsResponse.java
package com.example.badmintonshop.network.dto;

import com.google.gson.annotations.SerializedName;
import java.util.List;

public class ReviewDetailsResponse {

    @SerializedName("isSuccess")
    private boolean isSuccess;

    @SerializedName("message")
    private String message;

    // Tên "orderDetails" phải khớp với key trong JSON của PHP
    @SerializedName("orderDetails")
    private List<OrderDetailDto> orderDetails;

    // Getters
    public boolean isSuccess() { return isSuccess; }
    public String getMessage() { return message; }
    public List<OrderDetailDto> getOrderDetails() { return orderDetails; }
}