package com.example.badmintonshop.model;

public abstract class FilterItem {
    // Hằng số để xác định loại view
    public static final int TYPE_HEADER = 0;
    public static final int TYPE_OPTION = 1;

    public String name;

    // Phương thức trừu tượng để mỗi lớp con phải định nghĩa loại của nó
    public abstract int getType();
}