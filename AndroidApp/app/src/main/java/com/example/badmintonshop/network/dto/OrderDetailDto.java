package com.example.badmintonshop.network.dto;

import com.google.gson.annotations.SerializedName;
import java.io.Serializable;

public class OrderDetailDto implements Serializable {

    // --- Fields ---
    @SerializedName("orderDetailID")
    private int orderDetailID;

    @SerializedName("variantID")
    private int variantID;

    @SerializedName("productID")
    private int productID;

    @SerializedName("productName")
    private String productName;

    @SerializedName("imageUrl")
    private String imageUrl;

    @SerializedName("quantity")
    private int quantity;

    @SerializedName("price")
    private double price;

    @SerializedName("variantDetails")
    private String variantDetails;

    @SerializedName("isReviewed")
    private boolean isReviewed;

    // ⭐ BỔ SUNG CÁC TRƯỜNG SALE TỪ BACKEND
    // (Khớp với dữ liệu trả về từ orders/get_by_customer.php)
    @SerializedName("originalPrice")
    private double originalPrice;

    @SerializedName("isDiscounted")
    private boolean isDiscounted;
    // ⭐ KẾT THÚC BỔ SUNG

    // --------------------------------------------------
    // 1. Constructors
    // --------------------------------------------------

    // Constructor không tham số (Bắt buộc cho Gson/Retrofit)
    public OrderDetailDto() {
    }

    // Constructor đầy đủ tham số
    public OrderDetailDto(int orderDetailID, int variantID, int productID, String productName,
                          String imageUrl, int quantity, double price, String variantDetails, boolean isReviewed,
                          double originalPrice, boolean isDiscounted) { // ⭐ Cập nhật tham số
        this.orderDetailID = orderDetailID;
        this.variantID = variantID;
        this.productID = productID;
        this.productName = productName;
        this.imageUrl = imageUrl;
        this.quantity = quantity;
        this.price = price;
        this.variantDetails = variantDetails;
        this.isReviewed = isReviewed;
        this.originalPrice = originalPrice;
        this.isDiscounted = isDiscounted;
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

    public int getVariantID() {
        return variantID;
    }

    public void setVariantID(int variantID) {
        this.variantID = variantID;
    }

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

    public double getPrice() {
        return price;
    }

    public void setPrice(double price) {
        this.price = price;
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

    // ⭐ GETTER / SETTER MỚI CHO GIÁ SALE
    public double getOriginalPrice() {
        return originalPrice;
    }

    public void setOriginalPrice(double originalPrice) {
        this.originalPrice = originalPrice;
    }

    public boolean isDiscounted() {
        return isDiscounted;
    }

    public void setDiscounted(boolean discounted) {
        isDiscounted = discounted;
    }
    // ⭐ KẾT THÚC GETTER / SETTER MỚI
}