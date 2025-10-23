package com.example.badmintonshop.network.dto;

import com.google.gson.annotations.SerializedName;

import java.io.Serializable;

public class ApiResponse implements Serializable {

    // SỬA LỖI: Phải sử dụng tên trường "isSuccess" để khớp chính xác với JSON từ PHP
    @SerializedName("isSuccess")
    private boolean isSuccess;

    @SerializedName("message")
    private String message;

    @SerializedName("orderID")
    private Integer orderID; // Có thể null

    // ⭐ THÊM TRƯỜNG NÀY ĐỂ NHẬN URL TỪ SERVER KHI THANH TOÁN VNPAY ⭐
    @SerializedName("vnpayUrl")
    private String vnpayUrl;

    // ⭐ THÊM TRƯỜNG NÀY CHO CÁC PHẢN HỒI EMAIL (VÍ DỤ: "sent" HOẶC "failed_to_send")
    @SerializedName("emailStatus")
    private String emailStatus;

    // Constructor mặc định (cần thiết cho Gson)
    public ApiResponse() {
    }

    // Getters và Setters (đã được thêm hoàn chỉnh)

    public boolean isSuccess() {
        return isSuccess;
    }

    public String getMessage() {
        return message;
    }

    public Integer getOrderID() {
        return orderID;
    }

    public String getVnpayUrl() {
        return vnpayUrl;
    }

    public String getEmailStatus() {
        return emailStatus;
    }

    public void setMessage(String message) {
        this.message = message;
    }

    // Setter for testing purposes (optional)
    public void setVnpayUrl(String vnpayUrl) {
        this.vnpayUrl = vnpayUrl;
    }
}
