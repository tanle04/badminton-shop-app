package com.example.badmintonshop.model;

public class FilterHeader extends FilterItem {
    public boolean isExpanded = true; // Mặc định là đang mở

    public FilterHeader(String name) {
        this.name = name;
    }

    @Override
    public int getType() {
        return TYPE_HEADER;
    }
}