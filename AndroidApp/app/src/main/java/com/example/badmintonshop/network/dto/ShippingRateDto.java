package com.example.badmintonshop.network.dto;

import java.io.Serializable;

public class ShippingRateDto implements Serializable {
    // Phải khớp với JSON trả về từ API get_rates.php
    private int rateID;
    private String carrierName;
    private String serviceName;
    private String estimatedDelivery;
    private double shippingFee;
    private boolean isFreeShip; // Mới thêm: Có miễn phí vận chuyển không

    // Constructor (Tùy chọn, có thể bỏ qua nếu dùng Gson)
    public ShippingRateDto() {
    }

    // Getters
    public int getRateID() {
        return rateID;
    }

    public String getCarrierName() {
        return carrierName;
    }

    public String getServiceName() {
        return serviceName;
    }

    public String getEstimatedDelivery() {
        return estimatedDelivery;
    }

    public double getShippingFee() {
        return shippingFee;
    }

    public boolean isFreeShip() {
        return isFreeShip;
    }

    // Setters (Tùy chọn, thường không cần nếu dùng Gson)
    // ...
}