package com.example.badmintonshop.model;

import android.net.Uri; // Cần import Uri
import com.example.badmintonshop.network.dto.OrderDetailDto;
import java.io.Serializable;
import java.util.ArrayList;
import java.util.List;

// Model để chứa dữ liệu OrderDetail + trạng thái đánh giá của người dùng
public class ReviewItemModel implements Serializable {

    private OrderDetailDto orderDetail;
    private int rating = 5; // Mặc định 5 sao
    private String reviewContent = "";

    // ⭐ BỔ SUNG: Trường lưu trữ URI tệp (Ảnh và Video)
    private List<Uri> photoUris = new ArrayList<>(); // Cho phép nhiều ảnh
    private Uri videoUri = null;                    // Chỉ một video

    public ReviewItemModel(OrderDetailDto orderDetail) {
        this.orderDetail = orderDetail;
    }

    // Getters
    public OrderDetailDto getOrderDetail() { return orderDetail; }
    public int getRating() { return rating; }
    public String getReviewContent() { return reviewContent; }

    // ⭐ GETTERS/SETTERS CHO MEDIA
    public List<Uri> getPhotoUris() {
        return photoUris;
    }

    public Uri getVideoUri() {
        return videoUri;
    }

    // Setters (Dùng để cập nhật dữ liệu từ EditText/RatingBar)
    public void setRating(int rating) { this.rating = rating; }
    public void setReviewContent(String reviewContent) { this.reviewContent = reviewContent; }

    // ⭐ SETTERS CHO MEDIA
    public void setPhotoUris(List<Uri> photoUris) {
        // Đảm bảo không null
        this.photoUris = photoUris != null ? photoUris : new ArrayList<>();
    }

    public void setVideoUri(Uri videoUri) {
        this.videoUri = videoUri;
    }

    // ⭐ HÀM CONVENIENCE: Lấy các ID cần thiết cho ReviewSubmitRequest

    // Lưu ý: orderDetailID cần phải có trong ReviewSubmitRequest
    public int getOrderDetailID() {
        return orderDetail.getOrderDetailID();
    }

    // Lưu ý: productID cần thiết cho logic API (cần thêm vào OrderDetailDto hoặc tính toán trong API)
    // Nếu OrderDetailDto không có ProductID, bạn sẽ cần lấy nó từ VariantID
    // Giả sử API của bạn cần ProductID, bạn có thể thêm hàm này (tùy thuộc vào thiết kế DTO)
    // public int getProductID() {
    //     return orderDetail.getProductID();
    // }
}