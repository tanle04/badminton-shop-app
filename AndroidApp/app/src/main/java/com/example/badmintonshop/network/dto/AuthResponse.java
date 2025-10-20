package com.example.badmintonshop.network.dto;

import com.example.badmintonshop.model.Customer; // ⭐ 1. IMPORT LỚP CUSTOMER ĐÃ ĐỊNH NGHĨA
import com.google.gson.annotations.SerializedName;

/**
 * Dùng cho phản hồi từ API login / register.
 */
public class AuthResponse {

    @SerializedName("message")
    private String message;

    @SerializedName("error")
    private String error;

    // ⭐ 2. SỬ DỤNG LỚP CUSTOMER ĐÃ CẬP NHẬT TRẠNG THÁI XÁC NHẬN
    @SerializedName("user")
    private Customer user; // Đổi từ User sang Customer

    // Getter & Setter
    public String getMessage() { return message; }
    public void setMessage(String message) { this.message = message; }

    public String getError() { return error; }
    public void setError(String error) { this.error = error; }

    // Đổi kiểu trả về của getUser()
    public Customer getUser() { return user; }
    public void setUser(Customer user) { this.user = user; }

    /*
     * ⭐ Lớp con "public static class User" ĐÃ BỊ LOẠI BỎ
     * và được thay thế bằng lớp Customer độc lập.
     */

    @Override
    public String toString() {
        return "AuthResponse{" +
                "message='" + message + '\'' +
                ", error='" + error + '\'' +
                ", user=" + user +
                '}';
    }
}