package com.example.badmintonshop.network.dto;

import com.google.gson.annotations.SerializedName;
import java.util.ArrayList;
import java.util.List;

public class ProductDto {

    // ====== Thông tin sản phẩm ======
    @SerializedName("productID")
    private int productID;

    @SerializedName("productName")
    private String productName;

    @SerializedName("description")
    private String description;

    // ⚠️ list.php dùng priceMin, detail.php dùng price
    @SerializedName(value = "priceMin", alternate = {"price"})
    private double price;

    @SerializedName("stockTotal")
    private int stockTotal;

    @SerializedName("brandName")
    private String brandName;

    @SerializedName("categoryName")
    private String categoryName;

    // ⭐ NEW: Tóm tắt đánh giá (Được trả về từ detail.php)
    @SerializedName("averageRating")
    private float averageRating;

    @SerializedName("totalReviews")
    private int totalReviews;

    // ====== Ảnh ======
    // list.php có imageUrl (1 ảnh), detail.php có images[] (nhiều ảnh)
    @SerializedName("imageUrl")
    private String imageUrl;

    @SerializedName("images")
    private List<ImageDto> images;

    // ====== Biến thể (size, trọng lượng, grip, giá riêng) ======
    @SerializedName("variants")
    private List<VariantDto> variants;

    // ⭐ THÊM: Constructor mặc định (cần thiết cho Gson)
    public ProductDto() {
    }

    // ====== GETTER / SETTER ======
    public int getProductID() { return productID; }
    public void setProductID(int productID) { this.productID = productID; }

    public String getProductName() { return productName; }
    public void setProductName(String productName) { this.productName = productName; }

    public String getDescription() { return description; }
    public void setDescription(String description) { this.description = description; }

    public double getPrice() { return price; }
    public void setPrice(double price) { this.price = price; }

    public int getStockTotal() { return stockTotal; }
    public void setStockTotal(int stockTotal) { this.stockTotal = stockTotal; }

    public String getBrandName() { return brandName; }
    public void setBrandName(String brandName) { this.brandName = brandName; }

    public String getCategoryName() { return categoryName; }
    public void setCategoryName(String categoryName) { this.categoryName = categoryName; }

    // ⭐ NEW: Getter và Setter cho Review Summary
    public float getAverageRating() { return averageRating; }
    public void setAverageRating(float averageRating) { this.averageRating = averageRating; }

    public int getTotalReviews() { return totalReviews; }
    public void setTotalReviews(int totalReviews) { this.totalReviews = totalReviews; }

    public String getImageUrl() { return imageUrl; }
    public void setImageUrl(String imageUrl) { this.imageUrl = imageUrl; }

    // ⭐ Cải tiến: Đảm bảo trả về ArrayList rỗng thay vì null
    public List<ImageDto> getImages() { return images != null ? images : new ArrayList<>(); }
    public void setImages(List<ImageDto> images) { this.images = images; }

    // ⭐ Cải tiến: Đảm bảo trả về ArrayList rỗng thay vì null
    public List<VariantDto> getVariants() { return variants != null ? variants : new ArrayList<>(); }
    public void setVariants(List<VariantDto> variants) { this.variants = variants; }

    // ====== Lớp con cho danh sách ảnh ======
    public static class ImageDto {
        @SerializedName("imageUrl")
        private String imageUrl;

        @SerializedName("imageType")
        private String imageType;

        @SerializedName("sortOrder")
        private int sortOrder;

        // ⭐ THÊM: Constructor mặc định
        public ImageDto() {}

        public String getImageUrl() { return imageUrl; }
        public String getImageType() { return imageType; }
        public int getSortOrder() { return sortOrder; }
    }

    // ====== Lớp con cho danh sách biến thể (size/giá) ======
    public static class VariantDto {
        @SerializedName("variantID")
        private int variantID;

        // ⭐ THÊM TRƯỜNG productID VÀO VARIANT ĐỂ DỄ DÀNG LẤY ID SẢN PHẨM CHA
        @SerializedName("productID")
        private int productID;

        @SerializedName("sku")
        private String sku;

        @SerializedName("price")
        private double price;

        @SerializedName("stock")
        private int stock;

        @SerializedName("attributes")
        private String attributes; // Ví dụ: "3U, G5" hoặc "L", "XL"

        // ⭐ THÊM: Constructor mặc định
        public VariantDto() {}

        // Getter
        public int getVariantID() { return variantID; }
        // ⭐ THÊM GETTER VÀ SETTER CHO PRODUCTID
        public int getProductID() { return productID; }
        public void setProductID(int productID) { this.productID = productID; }

        public String getSku() { return sku; }
        public double getPrice() { return price; }
        public int getStock() { return stock; }
        public String getAttributes() { return attributes; }
    }
}