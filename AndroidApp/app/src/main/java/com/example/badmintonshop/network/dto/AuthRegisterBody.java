package com.example.badmintonshop.network.dto;

public class AuthRegisterBody {
    private String fullName;
    private String email;
    private String password;
    private String phone;
    private String address;

    public AuthRegisterBody(String fullName, String email, String password, String phone, String address) {
        this.fullName = fullName;
        this.email = email;
        this.password = password;
        this.phone = phone;
        this.address = address;
    }

    // ✅ Getter & Setter (quan trọng cho Retrofit/Gson)
    public String getFullName() { return fullName; }
    public void setFullName(String fullName) { this.fullName = fullName; }

    public String getEmail() { return email; }
    public void setEmail(String email) { this.email = email; }

    public String getPassword() { return password; }
    public void setPassword(String password) { this.password = password; }

    public String getPhone() { return phone; }
    public void setPhone(String phone) { this.phone = phone; }

    public String getAddress() { return address; }
    public void setAddress(String address) { this.address = address; }
}
