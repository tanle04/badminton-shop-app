package com.example.badmintonshop.network.dto;

import com.google.gson.annotations.SerializedName;

public class WishlistAddRequest {

    @SerializedName("customerID")
    private int customerId;

    @SerializedName("productID")
    private int productId;

    // Constructor bắt buộc để tạo đối tượng request
    public WishlistAddRequest(int customerId, int productId) {
        this.customerId = customerId;
        this.productId = productId;
    }

    // Getter/Setter không bắt buộc nếu chỉ dùng để POST, nhưng có thể thêm vào
    // nếu cần truy cập dữ liệu sau khi tạo.
    public int getCustomerId() {
        return customerId;
    }

    public int getProductId() {
        return productId;
    }
}