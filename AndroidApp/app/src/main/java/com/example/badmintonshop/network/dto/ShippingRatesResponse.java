package com.example.badmintonshop.network.dto;

import java.util.List;

public class ShippingRatesResponse {
    private boolean success;
    private String message;
    private List<ShippingRateDto> data; // Danh sách các Rate

    public boolean isSuccess() {
        return success;
    }

    public String getMessage() {
        return message;
    }

    public List<ShippingRateDto> getData() {
        return data;
    }
}