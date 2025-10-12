package com.example.badmintonshop.network.dto;

import com.google.gson.annotations.SerializedName;

/**
 * Dùng cho phản hồi từ API login / register.
 */
public class AuthResponse {

    @SerializedName("message")
    private String message;

    @SerializedName("error")
    private String error;

    @SerializedName("user")
    private User user;

    // Getter & Setter
    public String getMessage() { return message; }
    public void setMessage(String message) { this.message = message; }

    public String getError() { return error; }
    public void setError(String error) { this.error = error; }

    public User getUser() { return user; }
    public void setUser(User user) { this.user = user; }

    // Lớp con đại diện cho user object
    public static class User {
        @SerializedName("customerID")
        private int customerID;

        @SerializedName("fullName")
        private String fullName;

        @SerializedName("email")
        private String email;

        @SerializedName("phone")
        private String phone;

        @SerializedName("address")
        private String address;

        public int getCustomerID() { return customerID; }
        public String getFullName() { return fullName; }
        public String getEmail() { return email; }
        public String getPhone() { return phone; }
        public String getAddress() { return address; }

        @Override
        public String toString() {
            return "User{" +
                    "customerID=" + customerID +
                    ", fullName='" + fullName + '\'' +
                    ", email='" + email + '\'' +
                    ", phone='" + phone + '\'' +
                    ", address='" + address + '\'' +
                    '}';
        }
    }

    @Override
    public String toString() {
        return "AuthResponse{" +
                "message='" + message + '\'' +
                ", error='" + error + '\'' +
                ", user=" + user +
                '}';
    }
}
