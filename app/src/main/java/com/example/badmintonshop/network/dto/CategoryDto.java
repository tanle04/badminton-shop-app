package com.example.badmintonshop.network.dto;

import com.google.gson.annotations.SerializedName;

public class CategoryDto {
    @SerializedName("categoryID")
    private int categoryID;

    @SerializedName("categoryName")
    private String categoryName;

    public int getCategoryID() {
        return categoryID;
    }

    public String getCategoryName() {
        return categoryName;
    }
}
