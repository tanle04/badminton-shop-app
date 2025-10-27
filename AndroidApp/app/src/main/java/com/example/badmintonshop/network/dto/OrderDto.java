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
    private String status;

    @SerializedName("total")
    private double total;

    @SerializedName("paymentMethod")
    private String paymentMethod;

    // ⭐ THÊM MỚI: Thông tin phí ship
    @SerializedName("shippingFee")
    private double shippingFee;

    @SerializedName("isFreeShip")
    private boolean isFreeShip;
    // ⭐ KẾT THÚC THÊM MỚI

    // ⭐ THÊM: Subtotal và Voucher Discount đã được tính toán bởi Server
    @SerializedName("subtotal")
    private double subtotal; // Tổng tiền hàng (trước phí ship và voucher)

    @SerializedName("voucherDiscountAmount")
    private double voucherDiscountAmount; // Giá trị giảm thực tế của voucher
    // ⭐ KẾT THÚC THÊM

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

    // ⭐ GETTERS MỚI CHO PHÍ SHIP
    public double getShippingFee() {
        return shippingFee;
    }

    public boolean isFreeShip() {
        return isFreeShip;
    }
    // ⭐ KẾT THÚC GETTERS MỚI

    // GETTERS MỚI CHO TỔNG KẾT
    public double getSubtotal() {
        return subtotal;
    }

    public double getVoucherDiscountAmount() {
        return voucherDiscountAmount;
    }
    // KẾT THÚC GETTERS MỚI

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

    // GETTERS cho Voucher
    public String getVoucherCode() {
        return voucherCode;
    }
    // Giữ lại getDiscountAmount() và trả về voucherDiscountAmount
    public double getDiscountAmount() {
        return voucherDiscountAmount;
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

    // ⭐ SETTERS MỚI CHO PHÍ SHIP
    public void setShippingFee(double shippingFee) {
        this.shippingFee = shippingFee;
    }

    public void setFreeShip(boolean freeShip) {
        isFreeShip = freeShip;
    }
    // ⭐ KẾT THÚC SETTERS MỚI

    // SETTERS MỚI CHO TỔNG KẾT
    public void setSubtotal(double subtotal) {
        this.subtotal = subtotal;
    }

    public void setVoucherDiscountAmount(double voucherDiscountAmount) {
        this.voucherDiscountAmount = voucherDiscountAmount;
    }
    // KẾT THÚC SETTERS MỚI

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

    // SETTERS cho Voucher
    public void setVoucherCode(String voucherCode) {
        this.voucherCode = voucherCode;
    }
    public void setDiscountAmount(double discountAmount) {
        this.voucherDiscountAmount = discountAmount; // Đảm bảo gán vào trường mới
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