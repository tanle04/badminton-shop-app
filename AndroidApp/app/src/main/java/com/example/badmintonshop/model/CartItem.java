package com.example.badmintonshop.model;

import com.google.gson.annotations.SerializedName; // Thêm import SerializedName
import java.io.Serializable;

// 🚩 SỬA ĐỔI: Thêm "implements Serializable"
public class CartItem implements Serializable {
    // Fields from your API response
    private int cartID;
    private int quantity;
    private int productID;
    private String productName;
    private int variantID;

    @SerializedName("variantPrice")
    private String variantPrice;

    @SerializedName("imageUrl")
    private String imageUrl;

    @SerializedName("variantDetails")
    private String variantDetails;

    // ⭐ THÊM: Trường tồn kho (lấy từ pv.stock trong API cart/get.php)
    @SerializedName("stock")
    private int stock;

    private boolean isSelected = false; // Mặc định chưa được chọn

    // --- Constructors ---
    // Constructor rỗng cần thiết cho Gson
    public CartItem() {}

    // Constructor đầy đủ (cập nhật để bao gồm stock)
    public CartItem(int cartID, int quantity, int productID, String productName, int variantID, String variantPrice, String imageUrl, String variantDetails, int stock) {
        this.cartID = cartID;
        this.quantity = quantity;
        this.productID = productID;
        this.productName = productName;
        this.variantID = variantID;
        this.variantPrice = variantPrice;
        this.imageUrl = imageUrl;
        this.variantDetails = variantDetails;
        this.stock = stock; // ⭐ KHỞI TẠO STOCK
    }

    // --- Getters and Setters ---
    public int getCartID() {
        return cartID;
    }

    public void setCartID(int cartID) {
        this.cartID = cartID;
    }

    public int getQuantity() {
        return quantity;
    }

    public void setQuantity(int quantity) {
        this.quantity = quantity;
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

    public int getVariantID() {
        return variantID;
    }

    public void setVariantID(int variantID) {
        this.variantID = variantID;
    }

    public String getVariantPrice() {
        return variantPrice;
    }

    public void setVariantPrice(String variantPrice) {
        this.variantPrice = variantPrice;
    }

    public String getImageUrl() {
        return imageUrl;
    }

    public void setImageUrl(String imageUrl) {
        this.imageUrl = imageUrl;
    }

    public String getVariantDetails() {
        return variantDetails;
    }

    public void setVariantDetails(String variantDetails) {
        this.variantDetails = variantDetails;
    }

    // ⭐ GETTER/SETTER MỚI cho Stock
    public int getStock() {
        return stock;
    }

    public void setStock(int stock) {
        this.stock = stock;
    }

    // Methods for the isSelected field
    public boolean isSelected() {
        return isSelected;
    }

    public void setSelected(boolean selected) {
        isSelected = selected;
    }
}