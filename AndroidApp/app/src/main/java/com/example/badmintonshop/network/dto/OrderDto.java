package com.example.badmintonshop.network.dto;

import com.google.gson.annotations.SerializedName;
import java.io.Serializable;
import java.util.List;

public class OrderDto implements Serializable {

    // --- Thông tin Đơn hàng chính ---
    @SerializedName("orderID")
    private int orderID;

    @SerializedName("orderDate")
    private String orderDate;

    @SerializedName("status")
    private String status; // Pending, Processing, Shipped, Delivered, Cancelled, Refunded

    @SerializedName("total")
    private double total;

    @SerializedName("paymentMethod")
    private String paymentMethod;

    // ⭐ ĐÃ THÊM: Thông tin Địa chỉ/Người nhận từ API
    @SerializedName("recipientName")
    private String recipientName;

    @SerializedName("phone")
    private String phone;

    @SerializedName("street")
    private String street;

    @SerializedName("city")
    private String city;

    // ⭐ ĐÃ THÊM: Thông tin Voucher từ API
    @SerializedName("voucherCode")
    private String voucherCode;

    // Trong API, bạn chỉ lấy discountValue từ bảng vouchers
    // Đối với màn hình hiển thị, ta dùng nó làm giá trị giảm tối đa/thực tế
    @SerializedName("discountValue")
    private double discountAmount;

    // Giả định phí ship là hằng số hoặc đã được tính vào tổng tiền,
    // nên không cần trường riêng ở đây trừ khi bạn muốn hiển thị chi tiết.

    // --- Chi tiết sản phẩm ---
    @SerializedName("items")
    private List<OrderDetailDto> items;

    // ⭐ CONSTRUCTOR RỖNG (Quan trọng cho các thư viện như Gson)
    public OrderDto() {
    }

    // --- Getters ---
    public int getOrderID() {
        return orderID;
    }
    public String getOrderDate() {
        return orderDate;
    }
    public String getStatus() {
        return status;
    }
    public double getTotal() {
        return total;
    }
    public String getPaymentMethod() {
        return paymentMethod;
    }
    public List<OrderDetailDto> getItems() {
        return items;
    }

    // GETTERS cho Địa chỉ/Người nhận
    public String getRecipientName() {
        return recipientName;
    }
    public String getPhone() {
        return phone;
    }
    public String getStreet() {
        return street;
    }
    public String getCity() {
        return city;
    }

    // ⭐ GETTERS mới cho Voucher
    public String getVoucherCode() {
        return voucherCode;
    }
    public double getDiscountAmount() {
        return discountAmount;
    }


    // --- Setters ---
    public void setOrderID(int orderID) {
        this.orderID = orderID;
    }
    public void setOrderDate(String orderDate) {
        this.orderDate = orderDate;
    }
    public void setStatus(String status) {
        this.status = status;
    }
    public void setTotal(double total) {
        this.total = total;
    }
    public void setPaymentMethod(String paymentMethod) {
        this.paymentMethod = paymentMethod;
    }
    public void setItems(List<OrderDetailDto> items) {
        this.items = items;
    }

    // SETTERS cho Địa chỉ/Người nhận
    public void setRecipientName(String recipientName) {
        this.recipientName = recipientName;
    }
    public void setPhone(String phone) {
        this.phone = phone;
    }
    public void setStreet(String street) {
        this.street = street;
    }
    public void setCity(String city) {
        this.city = city;
    }

    // ⭐ SETTERS mới cho Voucher
    public void setVoucherCode(String voucherCode) {
        this.voucherCode = voucherCode;
    }
    public void setDiscountAmount(double discountAmount) {
        this.discountAmount = discountAmount;
    }


    // --- Phương thức tiện ích ---
    public int getTotalItemsCount() {
        int count = 0;
        if (items != null) {
            for (OrderDetailDto item : items) {
                count += item.getQuantity();
            }
        }
        return count;
    }
}