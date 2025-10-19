package com.example.badmintonshop.network.dto;

import com.google.gson.annotations.SerializedName;

public class ApiResponse {

    // SỬA LỖI: Phải sử dụng tên trường "isSuccess" để khớp chính xác với JSON từ PHP
    @SerializedName("isSuccess")
    private boolean isSuccess;

    @SerializedName("message")
    private String message;

    // Constructor mặc định (cần thiết cho Gson)
    public ApiResponse() {
    }

    // Getters để truy cập dữ liệu phản hồi
    public boolean isSuccess() {
        return isSuccess;
    }

    public String getMessage() {
        return message;
    }

    public void setMessage(String message) {
        this.message = message;
    }
}
