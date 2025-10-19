package com.example.badmintonshop.network.dto;

import com.google.gson.annotations.SerializedName;
import java.util.ArrayList;
import java.util.List;

public class AddressListResponse {

    // ⭐ SỬA: Đổi tên trường từ "success" thành "isSuccess"
    // hoặc dùng @SerializedName("success") để khớp với backend
    @SerializedName("isSuccess") // Giả sử backend dùng "isSuccess"
    private boolean isSuccess;

    // ⭐ THÊM: Trường message để xử lý thông báo lỗi từ server
    @SerializedName("message")
    private String message;

    @SerializedName("addresses")
    private List<AddressDto> addresses;

    // --- Getters ---

    public boolean isSuccess() {
        return isSuccess;
    }

    // ⭐ THÊM: Getter cho message
    public String getMessage() {
        return message;
    }

    public List<AddressDto> getAddresses() {
        // Cải tiến: Đảm bảo phương thức này không bao giờ trả về null.
        return addresses != null ? addresses : new ArrayList<>();
    }
}