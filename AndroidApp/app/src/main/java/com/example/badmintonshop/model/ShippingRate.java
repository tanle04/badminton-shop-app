package com.example.badmintonshop.model;

import java.io.Serializable;

/**
 * Model cho thông tin về một phương thức vận chuyển và phí tương ứng.
 */
public class ShippingRate implements Serializable {
    private int rateID;
    private String serviceName;
    private double price;
    private String estimatedDelivery;
    private String carrierName; // Thêm tên nhà vận chuyển

    public ShippingRate(int rateID, String serviceName, double price, String estimatedDelivery, String carrierName) {
        this.rateID = rateID;
        this.serviceName = serviceName;
        this.price = price;
        this.estimatedDelivery = estimatedDelivery;
        this.carrierName = carrierName;
    }

    // Getters
    public int getRateID() { return rateID; }
    public String getServiceName() { return serviceName; }
    public double getPrice() { return price; }
    public String getEstimatedDelivery() { return estimatedDelivery; }
    public String getCarrierName() { return carrierName; }

    // Setters (Nếu cần thiết cho Retrofit/Gson)
}
