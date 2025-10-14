package com.example.badmintonshop.network.dto;

import com.google.gson.annotations.SerializedName;
import java.util.List;

public class WishlistGetResponse {
    @SerializedName("success")
    private boolean success;

    @SerializedName("count")
    private int count;

    // API PHP trả về danh sách các sản phẩm dưới key "wishlist"
    @SerializedName("wishlist")
    private List<ProductDto> wishlist;

    public boolean isSuccess() {
        return success;
    }

    public int getCount() {
        return count;
    }

    public List<ProductDto> getWishlist() {
        return wishlist;
    }
}