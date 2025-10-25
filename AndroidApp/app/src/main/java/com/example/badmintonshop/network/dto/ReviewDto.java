package com.example.badmintonshop.network.dto;

import com.google.gson.annotations.SerializedName;
import java.util.List;

public class ReviewDto {

    @SerializedName("reviewID")
    private int reviewID;

    @SerializedName("customerID")
    private int customerID;

    @SerializedName("customerName")
    private String customerName;

    @SerializedName("rating")
    private int rating; // 1 đến 5 sao

    @SerializedName("reviewContent")
    private String reviewContent;

    @SerializedName("reviewDate")
    private String reviewDate; // Hoặc kiểu Date/Long tùy thuộc vào cách bạn xử lý ngày

    // Tương ứng với chuỗi ảnh 'mediaUrl1||mediaUrl2' được explode thành List<String>
    @SerializedName("reviewPhotos")
    private List<String> reviewPhotos;

    // Constructor mặc định
    public ReviewDto() {}

    // Getters
    public int getReviewID() { return reviewID; }
    public int getCustomerID() { return customerID; }
    public String getCustomerName() { return customerName; }
    public int getRating() { return rating; }
    public String getReviewContent() { return reviewContent; }
    public String getReviewDate() { return reviewDate; }
    public List<String> getReviewPhotos() { return reviewPhotos; }

    // Setters (Nếu cần thiết, nhưng thường không cần cho DTO phản hồi)
    // ...
}