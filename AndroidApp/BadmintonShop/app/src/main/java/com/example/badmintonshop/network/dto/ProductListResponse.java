package com.example.badmintonshop.network.dto;

import com.google.gson.annotations.SerializedName;
import java.util.List;

public class ProductListResponse {

    @SerializedName("page")
    private int page;

    @SerializedName("items")
    private List<ProductDto> items;

    public int getPage() { return page; }
    public List<ProductDto> getItems() { return items; }

    @Override
    public String toString() {
        return "ProductListResponse{" +
                "page=" + page +
                ", items=" + (items != null ? items.size() : 0) +
                '}';
    }
}
