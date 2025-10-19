package com.example.badmintonshop.network.dto;

import com.google.gson.annotations.SerializedName;
import java.io.Serializable;

// Implement Serializable để có thể truyền object qua Intent
public class AddressDto implements Serializable {

    @SerializedName("addressID")
    private int addressID;
    @SerializedName("customerID")
    private int customerID;
    @SerializedName("recipientName")
    private String recipientName;
    @SerializedName("phone")
    private String phone;
    @SerializedName("street")
    private String street;
    @SerializedName("city")
    private String city;
    @SerializedName("postalCode")
    private String postalCode;
    @SerializedName("country")
    private String country;
    @SerializedName("isDefault")
    private boolean isDefault;

    // Getters
    public int getAddressID() { return addressID; }
    public int getCustomerID() { return customerID; }
    public String getRecipientName() { return recipientName; }
    public String getPhone() { return phone; }
    public String getStreet() { return street; }
    public String getCity() { return city; }
    public String getPostalCode() { return postalCode; }
    public String getCountry() { return country; }
    public boolean isDefault() { return isDefault; }
}