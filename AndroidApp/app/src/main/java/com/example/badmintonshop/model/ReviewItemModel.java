package com.example.badmintonshop.model;

import android.net.Uri;
import com.example.badmintonshop.network.dto.OrderDetailDto;
import java.io.Serializable;
import java.util.ArrayList;
import java.util.List;

// Model để chứa dữ liệu OrderDetail + trạng thái đánh giá của người dùng
public class ReviewItemModel implements Serializable {

    private OrderDetailDto orderDetail;
    private int rating = 5; // Mặc định 5 sao
    private String reviewContent = "";

    // ⭐ SỬA: Biến thành viên phải là List<Uri> cho cả Ảnh và Video
    private List<Uri> photoUris = new ArrayList<>(); // Cho phép nhiều ảnh
    private List<Uri> videoUris = new ArrayList<>();             // ⭐ ĐÃ SỬA: Cho phép nhiều video (List)

    public ReviewItemModel(OrderDetailDto orderDetail) {
        this.orderDetail = orderDetail;
    }

    // Getters
    public OrderDetailDto getOrderDetail() { return orderDetail; }
    public int getRating() { return rating; }
    public String getReviewContent() { return reviewContent; }

    // ⭐ GETTERS CHO MEDIA (Đảm bảo trả về List không null)
    public List<Uri> getPhotoUris() {
        if (photoUris == null) {
            photoUris = new ArrayList<>();
        }
        return photoUris;
    }

    public List<Uri> getVideoUris() {
        if (videoUris == null) {
            videoUris = new ArrayList<>();
        }
        return videoUris;
    }

    // Setters (Dùng để cập nhật dữ liệu từ EditText/RatingBar)
    public void setRating(int rating) { this.rating = rating; }
    public void setReviewContent(String reviewContent) { this.reviewContent = reviewContent; }

    // ⭐ SETTERS CHO MEDIA (SỬA: Xử lý List<Uri> cho Video)
    public void setPhotoUris(List<Uri> photoUris) {
        // Đảm bảo không null
        this.photoUris = photoUris != null ? photoUris : new ArrayList<>();
    }

    public void setVideoUris(List<Uri> videoUris) { // ⭐ THAY THẾ: setVideoUri -> setVideoUris
        this.videoUris = videoUris != null ? videoUris : new ArrayList<>();
    }

    // ⭐ LƯU Ý: setVideoUri cũ đã bị xóa.
    // Nếu bạn muốn hàm tiện ích cho 1 video, bạn cần thêm logic này:
    // public void addVideoUri(Uri videoUri) {
    //     getVideoUris().add(videoUri);
    // }


    // HÀM CONVENIENCE: Lấy các ID cần thiết cho ReviewSubmitRequest
    public int getOrderDetailID() {
        return orderDetail.getOrderDetailID();
    }
    // ... (Các hàm tiện ích khác) ...
}