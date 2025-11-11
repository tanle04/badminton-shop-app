package com.example.badmintonshop.network.dto;

import com.google.gson.annotations.SerializedName;

public class BrandDto {

    @SerializedName("brandID")
    private int brandID;

    @SerializedName("brandName")
    private String brandName;

    // Getters
    public int getBrandID() {
        return brandID;
    }

    public String getBrandName() {
        return brandName;
    }
}