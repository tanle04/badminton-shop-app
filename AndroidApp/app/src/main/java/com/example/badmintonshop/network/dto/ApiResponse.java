package com.example.badmintonshop.network.dto;

import com.google.gson.annotations.SerializedName;

public class ApiResponse {

    // Phản ánh trường "success" từ JSON
    @SerializedName("success")
    private boolean success;

    // Phản ánh trường "message" từ JSON
    @SerializedName("message")
    private String message;

    // Constructor mặc định (cần thiết cho Gson)
    public ApiResponse() {
    }

    // Getters để truy cập dữ liệu phản hồi
    public boolean isSuccess() {
        return success;
    }

    public String getMessage() {
        return message;
    }
}