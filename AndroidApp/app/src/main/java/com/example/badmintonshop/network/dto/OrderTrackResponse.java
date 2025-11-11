package com.example.badmintonshop.network.dto;

import com.google.gson.annotations.SerializedName;

public class OrderTrackResponse {
    @SerializedName("isSuccess")
    private boolean isSuccess;

    @SerializedName("message")
    private String message;

    @SerializedName("data")
    private OrderTrackData data;

    // Getters
    public boolean isSuccess() { return isSuccess; }
    public String getMessage() { return message; }
    public OrderTrackData getData() { return data; }
}