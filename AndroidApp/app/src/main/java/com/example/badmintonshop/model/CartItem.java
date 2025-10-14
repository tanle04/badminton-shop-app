package com.example.badmintonshop.model;

public class CartItem {
    // Fields from your API response
    private int cartID;
    private int quantity;
    private int productID;
    private String productName;
    private int variantID;
    private String variantPrice;
    private String imageUrl;
    private String variantDetails;

    // 🚩 ADD THIS FIELD to track the checkbox state
    private boolean isSelected = false; // Default to not selected

    // --- Constructors ---
    // An empty constructor is needed for some libraries
    public CartItem() {}

    public CartItem(int cartID, int quantity, int productID, String productName, int variantID, String variantPrice, String imageUrl, String variantDetails) {
        this.cartID = cartID;
        this.quantity = quantity;
        this.productID = productID;
        this.productName = productName;
        this.variantID = variantID;
        this.variantPrice = variantPrice;
        this.imageUrl = imageUrl;
        this.variantDetails = variantDetails;
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

    // 🚩 ADD THESE TWO METHODS for the isSelected field
    public boolean isSelected() {
        return isSelected;
    }

    public void setSelected(boolean selected) {
        isSelected = selected;
    }
}