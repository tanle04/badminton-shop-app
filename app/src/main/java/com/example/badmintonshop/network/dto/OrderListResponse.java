package com.example.badmintonshop.network.dto;

import com.google.gson.annotations.SerializedName;
import java.util.List;

public class OrderListResponse {
    @SerializedName("isSuccess")
    private boolean isSuccess;

    @SerializedName("message")
    private String message;

    @SerializedName("orders")
    private List<OrderDto> orders;


    public boolean isSuccess() { return isSuccess; }
    public String getMessage() { return message; }
    public List<OrderDto> getOrders() { return orders; }
}