package com.example.badmintonshop.network.dto;

import com.google.gson.annotations.SerializedName;
import java.util.List;

public class ReviewListResponse extends ApiResponse { // Kế thừa ApiResponse (isSuccess, message)

    @SerializedName("productID")
    private int productID;

    // ⭐ TÙY CHỌN: Nếu API chi tiết Review của bạn cũng trả về tóm tắt (nên có)
    @SerializedName("averageRating")
    private float averageRating;

    @SerializedName("totalReviews")
    private int totalReviews;

    @SerializedName("items")
    private List<ReviewDto> items;

    // Constructor mặc định
    public ReviewListResponse() {}

    // Getters (Bạn có thể bỏ qua Getters của isSuccess và message vì chúng đã có trong ApiResponse)

    public int getProductID() { return productID; }
    public float getAverageRating() { return averageRating; }
    public int getTotalReviews() { return totalReviews; }
    public List<ReviewDto> getItems() { return items; }
}