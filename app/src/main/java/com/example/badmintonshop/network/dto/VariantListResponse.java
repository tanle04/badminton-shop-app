package com.example.badmintonshop.network.dto; // Đảm bảo đúng package

import com.google.gson.annotations.SerializedName;
import java.util.ArrayList; // Import ArrayList
import java.util.List;

public class VariantListResponse {

    // ⭐ SỬA: Đổi tên trường thành "isSuccess" và dùng SerializedName để khớp với API
    @SerializedName("isSuccess")
    private boolean isSuccess;

    // ⭐ THÊM: Trường message để xử lý thông báo lỗi từ server
    @SerializedName("message")
    private String message;

    // Tên "variants" phải khớp với key trong JSON mà API trả về
    @SerializedName("variants")
    private List<ProductDto.VariantDto> variants;

    // --- Getters ---

    public boolean isSuccess() {
        return isSuccess;
    }

    public String getMessage() {
        return message;
    }

    public List<ProductDto.VariantDto> getVariants() {
        // ⭐ Cải tiến: Trả về ArrayList rỗng thay vì null để tránh NullPointerException
        return variants != null ? variants : new ArrayList<>();
    }
}