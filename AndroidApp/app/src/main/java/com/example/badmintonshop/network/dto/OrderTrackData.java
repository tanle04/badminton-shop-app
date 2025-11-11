package com.example.badmintonshop.network.dto;

import com.google.gson.annotations.SerializedName;
import java.util.List;

public class OrderTrackData {

    // Tái sử dụng OrderDto của bạn
    @SerializedName("orderInfo")
    private OrderDto orderInfo;

    // Tái sử dụng OrderDetailDto từ OrderDto
    @SerializedName("products")
    private List<OrderDetailDto> products;

    @SerializedName("timelineSteps")
    private List<TimelineStep> timelineSteps;

    // Getters
    public OrderDto getOrderInfo() { return orderInfo; }
    public List<OrderDetailDto> getProducts() { return products; }
    public List<TimelineStep> getTimelineSteps() { return timelineSteps; }
}