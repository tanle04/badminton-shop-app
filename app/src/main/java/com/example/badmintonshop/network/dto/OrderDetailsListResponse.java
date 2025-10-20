package com.example.badmintonshop.network.dto;

import com.google.gson.annotations.SerializedName;
import java.util.ArrayList;
import java.util.List;

public class OrderDetailsListResponse {

    @SerializedName("isSuccess")
    private boolean isSuccess;

    @SerializedName("message")
    private String message;

    @SerializedName("orderDetails") // Khớp với key trong orders/get_details.php
    private List<OrderDetailDto> orderDetails;

    // Getters
    public boolean isSuccess() { return isSuccess; }
    public String getMessage() { return message; }
    public List<OrderDetailDto> getOrderDetails() { return orderDetails != null ? orderDetails : new ArrayList<>(); }
}