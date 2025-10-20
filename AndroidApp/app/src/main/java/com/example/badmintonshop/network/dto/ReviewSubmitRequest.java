package com.example.badmintonshop.network.dto;

import com.example.badmintonshop.model.ReviewItemModel;
import com.google.gson.annotations.SerializedName;
import java.util.ArrayList;
import java.util.List;

public class ReviewSubmitRequest {

    @SerializedName("orderID")
    private int orderID;

    @SerializedName("customerID")
    private int customerID;

    // Sử dụng DTO payload gọn nhẹ
    @SerializedName("reviews")
    private List<ReviewItemPayload> reviews;

    public ReviewSubmitRequest(int orderID, int customerID, List<ReviewItemModel> reviewModels) {
        this.orderID = orderID;
        this.customerID = customerID;

        // Chuyển đổi ReviewItemModel sang Payload gọn nhẹ
        this.reviews = convertToPayload(reviewModels);
    }

    // Hàm chuyển đổi từ Model sang Payload
    private List<ReviewItemPayload> convertToPayload(List<ReviewItemModel> reviewModels) {
        List<ReviewItemPayload> payloads = new ArrayList<>();
        for (ReviewItemModel model : reviewModels) {

            // ⭐ BẮT BUỘC: Lấy ProductID từ OrderDetailDto
            int productID = model.getOrderDetail().getProductID();

            payloads.add(new ReviewItemPayload(
                    model.getOrderDetail().getOrderDetailID(),
                    productID, // ⭐ THÊM ProductID
                    model.getRating(),
                    model.getReviewContent()
            ));
        }
        return payloads;
    }

    // Getters (tùy chọn)
    public int getOrderID() { return orderID; }
    public int getCustomerID() { return customerID; }
    public List<ReviewItemPayload> getReviews() { return reviews; }


    // ----------------------------------------------------
    // LỚP NỘI BỘ: REVIEW ITEM PAYLOAD (DTO trung gian)
    // ----------------------------------------------------
    private static class ReviewItemPayload {

        @SerializedName("orderDetailID")
        private int orderDetailID;

        // ⭐ BẮT BUỘC: Thêm trường ProductID để khớp với validation PHP
        @SerializedName("productID")
        private int productID;

        @SerializedName("rating")
        private int rating;

        @SerializedName("reviewContent")
        private String reviewContent;

        // Constructor cho payload (Đã thêm ProductID)
        public ReviewItemPayload(int orderDetailID, int productID, int rating, String reviewContent) {
            this.orderDetailID = orderDetailID;
            this.productID = productID;
            this.rating = rating;
            this.reviewContent = reviewContent;
        }

        // Getters (tùy chọn)
        public int getOrderDetailID() { return orderDetailID; }
        public int getProductID() { return productID; }
        public int getRating() { return rating; }
        public String getReviewContent() { return reviewContent; }
    }
}