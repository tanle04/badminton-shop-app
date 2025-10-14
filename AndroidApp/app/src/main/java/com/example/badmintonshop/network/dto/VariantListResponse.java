package com.example.badmintonshop.network.dto; // Đảm bảo đúng package

import com.google.gson.annotations.SerializedName;
import java.util.List;

public class VariantListResponse {

    @SerializedName("success")
    private boolean success;

    // Tên "variants" phải khớp với key trong JSON mà API trả về
    @SerializedName("variants")
    private List<ProductDto.VariantDto> variants;

    // Getters
    public boolean isSuccess() {
        return success;
    }

    public List<ProductDto.VariantDto> getVariants() {
        return variants;
    }
}