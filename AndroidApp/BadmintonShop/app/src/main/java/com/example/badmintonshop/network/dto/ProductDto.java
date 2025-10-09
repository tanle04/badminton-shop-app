package com.example.badmintonshop.network.dto;

public class ProductDto {
    private int productID;
    private String productName;
    private String description;
    private double priceMin;
    private int stockTotal;
    private String brandName;
    private String categoryName;
    private String imageUrl;

    public ProductDto(int productID, String imageUrl, String productName, String description, double priceMin, int stockTotal, String brandName, String categoryName) {
        this.productID = productID;
        this.imageUrl = imageUrl;
        this.productName = productName;
        this.description = description;
        this.priceMin = priceMin;
        this.stockTotal = stockTotal;
        this.brandName = brandName;
        this.categoryName = categoryName;
    }

    public void setProductID(int productID) {
        this.productID = productID;
    }

    public void setProductName(String productName) {
        this.productName = productName;
    }

    public void setDescription(String description) {
        this.description = description;
    }

    public void setPriceMin(double priceMin) {
        this.priceMin = priceMin;
    }

    public void setStockTotal(int stockTotal) {
        this.stockTotal = stockTotal;
    }

    public void setBrandName(String brandName) {
        this.brandName = brandName;
    }

    public void setCategoryName(String categoryName) {
        this.categoryName = categoryName;
    }

    public void setImageUrl(String imageUrl) {
        this.imageUrl = imageUrl;
    }

    public int getProductID() { return productID; }
    public String getProductName() { return productName; }
    public String getDescription() { return description; }
    public double getPriceMin() { return priceMin; }
    public int getStockTotal() { return stockTotal; }
    public String getBrandName() { return brandName; }
    public String getCategoryName() { return categoryName; }
    public String getImageUrl() { return imageUrl; }


}
