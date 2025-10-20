package com.example.badmintonshop.model;

public class FilterOption extends FilterItem {
    public boolean isSelected = false; // Mặc định là chưa được chọn

    public FilterOption(String name) {
        this.name = name;
    }

    @Override
    public int getType() {
        return TYPE_OPTION;
    }
}