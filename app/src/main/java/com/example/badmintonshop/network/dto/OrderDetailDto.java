package com.example.badmintonshop.network.dto;

import com.google.gson.annotations.SerializedName;
import java.io.Serializable;

public class OrderDetailDto implements Serializable {

    @SerializedName("orderDetailID")
    private int orderDetailID;

    // ⭐ BỔ SUNG: ID biến thể (Khóa ngoại trực tiếp trong DB)
    @SerializedName("variantID")
    private int variantID;

    // ⭐ BỔ SUNG: ID sản phẩm (Rất quan trọng cho logic đánh giá)
    @SerializedName("productID")
    private int productID;

    @SerializedName("productName")
    private String productName;

    @SerializedName("imageUrl")
    private String imageUrl; // Ảnh sản phẩm

    @SerializedName("quantity")
    private int quantity;

    // Trường chi tiết biến thể (ví dụ: Size: L, Màu: Đỏ)
    @SerializedName("variantDetails")
    private String variantDetails;

    // Trạng thái đã được đánh giá
    @SerializedName("isReviewed")
    private boolean isReviewed;

    // --------------------------------------------------
    // 1. Constructors (Phương thức khởi tạo)
    // --------------------------------------------------

    // Constructor không tham số (Bắt buộc cho Gson/Retrofit)
    public OrderDetailDto() {
    }

    // Constructor đầy đủ tham số (Cập nhật để bao gồm các ID mới)
    public OrderDetailDto(int orderDetailID, int variantID, int productID, String productName, String imageUrl, int quantity, String variantDetails, boolean isReviewed) {
        this.orderDetailID = orderDetailID;
        this.variantID = variantID;
        this.productID = productID;
        this.productName = productName;
        this.imageUrl = imageUrl;
        this.quantity = quantity;
        this.variantDetails = variantDetails;
        this.isReviewed = isReviewed;
    }

    // --------------------------------------------------
    // 2. Getters and Setters
    // --------------------------------------------------

    public int getOrderDetailID() {
        return orderDetailID;
    }

    public void setOrderDetailID(int orderDetailID) {
        this.orderDetailID = orderDetailID;
    }

    // ⭐ GETTER/SETTER mới cho VariantID
    public int getVariantID() {
        return variantID;
    }

    public void setVariantID(int variantID) {
        this.variantID = variantID;
    }

    // ⭐ GETTER/SETTER mới cho ProductID
    public int getProductID() {
        return productID;
    }

    public void setProductID(int productID) {
        this.productID = productID;
    }

    public String getProductName() {
        return productName;
    }

    public void setProductName(String productName) {
        this.productName = productName;
    }

    public String getImageUrl() {
        return imageUrl;
    }

    public void setImageUrl(String imageUrl) {
        this.imageUrl = imageUrl;
    }

    public int getQuantity() {
        return quantity;
    }

    public void setQuantity(int quantity) {
        this.quantity = quantity;
    }

    public String getVariantDetails() {
        return variantDetails;
    }

    public void setVariantDetails(String variantDetails) {
        this.variantDetails = variantDetails;
    }

    public boolean isReviewed() {
        return isReviewed;
    }

    public boolean getIsReviewed() {
        return isReviewed;
    }

    public void setReviewed(boolean reviewed) {
        isReviewed = reviewed;
    }
}