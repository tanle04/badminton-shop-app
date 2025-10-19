package com.example.badmintonshop.model;

import com.example.badmintonshop.network.dto.OrderDetailDto;

import java.io.Serializable;

// Model để chứa dữ liệu OrderDetail + trạng thái đánh giá của người dùng
public class ReviewItemModel implements Serializable {

    private OrderDetailDto orderDetail; // Chi tiết sản phẩm trong đơn hàng
    private int rating = 5; // Mặc định 5 sao
    private String reviewContent = "";

    public ReviewItemModel(OrderDetailDto orderDetail) {
        this.orderDetail = orderDetail;
    }

    // Getters
    public OrderDetailDto getOrderDetail() { return orderDetail; }
    public int getRating() { return rating; }
    public String getReviewContent() { return reviewContent; }

    // Setters (Dùng để cập nhật dữ liệu từ EditText/RatingBar)
    public void setRating(int rating) { this.rating = rating; }
    public void setReviewContent(String reviewContent) { this.reviewContent = reviewContent; }
}