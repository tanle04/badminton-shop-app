package com.example.badmintonshop.model;

import com.google.gson.annotations.SerializedName;

import java.io.Serializable; // Nên implement Serializable hoặc Parcelable
import java.util.Date; // Tùy chọn, dùng String cũng được nếu không cần thao tác Date

public class Customer implements Serializable {

    @SerializedName("customerID")
    private int customerID;

    @SerializedName("fullName")
    private String fullName;

    @SerializedName("email")
    private String email;

    @SerializedName("password_hash") // Mặc dù không gửi về client, nên có để đồng bộ với DB
    private String passwordHash;

    @SerializedName("phone")
    private String phone;

    // ⭐ CÁC TRƯỜNG MỚI ĐÃ THÊM VÀO DB ĐỂ HỖ TRỢ XÁC NHẬN EMAIL
    @SerializedName("isEmailVerified")
    private int isEmailVerified; // TINYINT(1) -> int, giá trị 0 hoặc 1

    @SerializedName("verificationToken")
    private String verificationToken; // VARCHAR(255)

    @SerializedName("tokenExpiry")
    private String tokenExpiry; // DATETIME -> String (hoặc Date)
    // ⭐ KẾT THÚC CÁC TRƯỜNG MỚI

    @SerializedName("createdDate")
    private String createdDate; // DATETIME -> String

    // Constructor rỗng cần thiết cho Gson/Retrofit
    public Customer() {
    }

    // --- Getters và Setters ---

    public int getCustomerID() {
        return customerID;
    }

    public void setCustomerID(int customerID) {
        this.customerID = customerID;
    }

    public String getFullName() {
        return fullName;
    }

    public void setFullName(String fullName) {
        this.fullName = fullName;
    }

    public String getEmail() {
        return email;
    }

    public void setEmail(String email) {
        this.email = email;
    }

    public String getPasswordHash() {
        return passwordHash;
    }

    public void setPasswordHash(String passwordHash) {
        this.passwordHash = passwordHash;
    }

    public String getPhone() {
        return phone;
    }

    public void setPhone(String phone) {
        this.phone = phone;
    }

    // ⭐ Getters/Setters cho trạng thái xác nhận

    public int getIsEmailVerified() {
        return isEmailVerified;
    }

    public void setIsEmailVerified(int isEmailVerified) {
        this.isEmailVerified = isEmailVerified;
    }

    public String getVerificationToken() {
        return verificationToken;
    }

    public void setVerificationToken(String verificationToken) {
        this.verificationToken = verificationToken;
    }

    public String getTokenExpiry() {
        return tokenExpiry;
    }

    public void setTokenExpiry(String tokenExpiry) {
        this.tokenExpiry = tokenExpiry;
    }

    // ---

    public String getCreatedDate() {
        return createdDate;
    }

    public void setCreatedDate(String createdDate) {
        this.createdDate = createdDate;
    }
}