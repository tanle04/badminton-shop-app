package com.example.badmintonshop.network.dto;

import com.google.gson.annotations.SerializedName;
import java.io.Serializable;
import java.util.List;

public class OrderDto implements Serializable {

    // --- Main Order Information ---
    @SerializedName("orderID")
    private int orderID;

    @SerializedName("orderDate")
    private String orderDate;

    @SerializedName("status")
    private String status;

    // ⭐ ADDED: Payment Status
    @SerializedName("paymentStatus")
    private String paymentStatus;
    // ⭐ END ADDITION

    @SerializedName("total")
    private double total;

    @SerializedName("paymentMethod")
    private String paymentMethod;

    // --- Shipping Information ---
    @SerializedName("shippingFee")
    private double shippingFee;

    @SerializedName("isFreeShip")
    private boolean isFreeShip;

    // --- Calculated Totals (from Server) ---
    @SerializedName("subtotal")
    private double subtotal; // Subtotal (before shipping and voucher)

    @SerializedName("voucherDiscountAmount")
    private double voucherDiscountAmount; // Actual discount value applied

    // --- Address/Recipient Information ---
    @SerializedName("recipientName")
    private String recipientName;

    @SerializedName("phone")
    private String phone;

    @SerializedName("street")
    private String street;

    @SerializedName("city")
    private String city;

    // --- Voucher Information ---
    @SerializedName("voucherCode")
    private String voucherCode;

    // --- Product Details ---
    @SerializedName("items")
    private List<OrderDetailDto> items;
    @SerializedName("shippingMethod")
    private String shippingMethod;

    @SerializedName("trackingCode")
    private String trackingCode;
    // --- Empty Constructor (Important for libraries like Gson) ---
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
    // ⭐ GETTER FOR PAYMENT STATUS
    public String getPaymentStatus() {
        return paymentStatus;
    }
    // ⭐ END GETTER
    public double getTotal() {
        return total;
    }
    public String getPaymentMethod() {
        return paymentMethod;
    }
    public List<OrderDetailDto> getItems() {
        return items;
    }
    public double getShippingFee() {
        return shippingFee;
    }
    public boolean isFreeShip() {
        return isFreeShip;
    }
    public double getSubtotal() {
        return subtotal;
    }
    public double getVoucherDiscountAmount() {
        return voucherDiscountAmount;
    }
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
    public String getVoucherCode() {
        return voucherCode;
    }
    // Kept getDiscountAmount() for compatibility, returns the calculated server discount
    public double getDiscountAmount() {
        return voucherDiscountAmount;
    }
    public String getShippingMethod() {
        return shippingMethod;
    }

    public String getTrackingCode() {
        return trackingCode;
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
    // ⭐ SETTER FOR PAYMENT STATUS
    public void setPaymentStatus(String paymentStatus) {
        this.paymentStatus = paymentStatus;
    }
    // ⭐ END SETTER
    public void setTotal(double total) {
        this.total = total;
    }
    public void setPaymentMethod(String paymentMethod) {
        this.paymentMethod = paymentMethod;
    }
    public void setItems(List<OrderDetailDto> items) {
        this.items = items;
    }
    public void setShippingFee(double shippingFee) {
        this.shippingFee = shippingFee;
    }
    public void setFreeShip(boolean freeShip) {
        isFreeShip = freeShip;
    }
    public void setSubtotal(double subtotal) {
        this.subtotal = subtotal;
    }
    public void setVoucherDiscountAmount(double voucherDiscountAmount) {
        this.voucherDiscountAmount = voucherDiscountAmount;
    }
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
    public void setVoucherCode(String voucherCode) {
        this.voucherCode = voucherCode;
    }
    // Maps setDiscountAmount to the correct field
    public void setDiscountAmount(double discountAmount) {
        this.voucherDiscountAmount = discountAmount;
    }
    public void setShippingMethod(String shippingMethod) {
        this.shippingMethod = shippingMethod;
    }

    public void setTrackingCode(String trackingCode) {
        this.trackingCode = trackingCode;
    }

    // --- Utility Method ---
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