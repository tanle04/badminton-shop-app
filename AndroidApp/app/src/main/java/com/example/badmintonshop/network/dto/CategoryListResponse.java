package com.example.badmintonshop.network.dto;

import com.google.gson.annotations.SerializedName;
import java.util.List;

public class CategoryListResponse {
    @SerializedName("success")
    private boolean success;

    @SerializedName("items")
    private List<CategoryDto> items;

    public boolean isSuccess() { return success; }
    public List<CategoryDto> getItems() { return items; }
}
