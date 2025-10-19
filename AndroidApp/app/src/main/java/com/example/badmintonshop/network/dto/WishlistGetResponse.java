package com.example.badmintonshop.network.dto;

import com.google.gson.annotations.SerializedName;
import java.util.ArrayList;
import java.util.List;

public class WishlistGetResponse {

    // ⭐ SỬA: Dùng @SerializedName("isSuccess") để nhất quán (Hoặc giữ "success" nếu API thực sự dùng "success")
    @SerializedName("isSuccess")
    private boolean isSuccess;

    // ⭐ THÊM: Trường message để xử lý thông báo lỗi từ server
    @SerializedName("message")
    private String message;

    @SerializedName("count")
    private int count;

    // API PHP trả về danh sách các sản phẩm dưới key "wishlist"
    @SerializedName("wishlist")
    private List<ProductDto> wishlist;

    // --- Getters ---

    public boolean isSuccess() {
        return isSuccess;
    }

    // ⭐ THÊM: Getter cho message
    public String getMessage() {
        return message;
    }

    public int getCount() {
        return count;
    }

    public List<ProductDto> getWishlist() {
        // ⭐ Cải tiến: Trả về ArrayList rỗng thay vì null để tránh NullPointerException ở lớp UI.
        return wishlist != null ? wishlist : new ArrayList<>();
    }
}