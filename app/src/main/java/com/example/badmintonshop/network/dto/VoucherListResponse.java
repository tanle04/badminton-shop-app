package com.example.badmintonshop.network.dto;

import com.google.gson.annotations.SerializedName;
import java.util.List;

/**
 * Phản hồi từ API lấy danh sách Voucher.
 * Cấu trúc JSON dự kiến:
 * {
 * "isSuccess": true,
 * "message": "Success message or error details",
 * "vouchers": [...]
 * }
 */
public class VoucherListResponse extends ApiResponse {

    @SerializedName("vouchers")
    private List<VoucherDto> vouchers;

    // Constructors (Mặc định cần cho Gson)
    public VoucherListResponse() {
    }

    // Getters và Setters

    public List<VoucherDto> getVouchers() {
        return vouchers;
    }

    public void setVouchers(List<VoucherDto> vouchers) {
        this.vouchers = vouchers;
    }
}