package com.example.badmintonshop.network.dto;

import com.google.gson.annotations.SerializedName;
import java.io.Serializable;
import java.text.DecimalFormat;
import java.math.BigDecimal;
import java.math.RoundingMode; // Thêm import RoundingMode

/**
 * Model cho dữ liệu Voucher (Mã giảm giá).
 */
public class VoucherDto implements Serializable {

    @SerializedName("voucherID")
    private int voucherID;

    @SerializedName("voucherCode")
    private String voucherCode;

    @SerializedName("description")
    private String description;

    @SerializedName("discountType")
    private String discountType; // 'percentage' hoặc 'fixed'

    @SerializedName("discountValue")
    private BigDecimal discountValue;

    @SerializedName("minOrderValue")
    private BigDecimal minOrderValue;

    @SerializedName("maxDiscountAmount")
    private BigDecimal maxDiscountAmount; // Có thể null

    // THÊM: Các trường được lấy từ SQL (StartDate, EndDate, Usage, Active)
    @SerializedName("startDate")
    private String startDate;

    @SerializedName("endDate")
    private String endDate; // Ngày hết hạn

    @SerializedName("maxUsage")
    private int maxUsage; // Cột này là maxUsage trong SQL

    @SerializedName("usedCount")
    private int usedCount; // Cột này là usedCount trong SQL

    @SerializedName("isActive")
    private boolean isActive;

    @SerializedName("isPrivate") // Cần thêm trường này vì PHP có trả về
    private boolean isPrivate;

    // 1. CONSTRUCTOR MẶC ĐỊNH
    public VoucherDto() {
    }

    // 2. CONSTRUCTOR ĐẦY ĐỦ THAM SỐ
    public VoucherDto(int voucherID, String voucherCode, String description, String discountType,
                      BigDecimal discountValue, BigDecimal minOrderValue, BigDecimal maxDiscountAmount,
                      int maxUsage, int usedCount, String startDate, String endDate,
                      boolean isActive, boolean isPrivate) {
        this.voucherID = voucherID;
        this.voucherCode = voucherCode;
        this.description = description;
        this.discountType = discountType;
        this.discountValue = discountValue;
        this.minOrderValue = minOrderValue;
        this.maxDiscountAmount = maxDiscountAmount;
        this.maxUsage = maxUsage;
        this.usedCount = usedCount;
        this.startDate = startDate;
        this.endDate = endDate;
        this.isActive = isActive;
        this.isPrivate = isPrivate;
    }

    // ⭐ HÀM TÍNH TOÁN MỚI: Trả về phần trăm còn lại của lượt sử dụng (0 - 100)
    /**
     * Trả về phần trăm lượt sử dụng còn lại (Remaining Usage Percent).
     * @return double từ 0.0 đến 100.0.
     */
    public double getUsageLimitPercent() {
        if (maxUsage <= 0) {
            return 100.0; // Không có giới hạn sử dụng hoặc không xác định
        }

        // Tính số lượt còn lại
        int remaining = maxUsage - usedCount;

        // Đảm bảo không âm
        if (remaining <= 0) {
            return 0.0;
        }

        // Tính phần trăm còn lại và làm tròn đến 2 chữ số thập phân
        return (double) remaining / maxUsage * 100.0;
    }

    // HÀM HỖ TRỢ HIỂN THỊ TRÊN UI (Đã cập nhật để xử lý BigDecimal)

    /**
     * Trả về mô tả ngắn gọn cho Voucher, ví dụ: "Giảm 15% (Max 50k)".
     */
    public String getDisplayDescription() {
        DecimalFormat formatter = new DecimalFormat("###,###");
        String valueStr;

        if ("fixed".equalsIgnoreCase(discountType) && discountValue != null) {
            // Chuyển BigDecimal sang double để định dạng hiển thị
            valueStr = "Giảm " + formatter.format(discountValue.doubleValue()) + "đ";
        } else if ("percentage".equalsIgnoreCase(discountType) && discountValue != null) {
            // Lấy giá trị phần trăm dưới dạng số nguyên
            valueStr = "Giảm " + discountValue.intValue() + "%";

            if (maxDiscountAmount != null && maxDiscountAmount.compareTo(BigDecimal.ZERO) > 0) {
                valueStr += " (Max " + formatter.format(maxDiscountAmount.doubleValue()) + "đ)";
            }
        } else {
            return description; // Fallback
        }

        return valueStr;
    }

    /**
     * Trả về điều kiện đơn hàng tối thiểu, ví dụ: "Đơn tối thiểu 500.000đ".
     */
    public String getMinOrderCondition() {
        DecimalFormat formatter = new DecimalFormat("###,###");
        if (minOrderValue != null && minOrderValue.compareTo(BigDecimal.ZERO) > 0) {
            return "Đơn tối thiểu " + formatter.format(minOrderValue.doubleValue()) + "đ";
        }
        return "Áp dụng cho mọi đơn hàng";
    }

    // GETTERS VÀ SETTERS (Đã cập nhật kiểu trả về và tham số)

    public int getVoucherID() {
        return voucherID;
    }

    public void setVoucherID(int voucherID) {
        this.voucherID = voucherID;
    }

    public String getVoucherCode() {
        return voucherCode;
    }

    public void setVoucherCode(String voucherCode) {
        this.voucherCode = voucherCode;
    }

    public String getDescription() {
        return description;
    }

    public void setDescription(String description) {
        this.description = description;
    }

    public String getDiscountType() {
        return discountType;
    }

    public void setDiscountType(String discountType) {
        this.discountType = discountType;
    }

    public BigDecimal getDiscountValue() {
        return discountValue;
    }

    public void setDiscountValue(BigDecimal discountValue) {
        this.discountValue = discountValue;
    }

    public BigDecimal getMinOrderValue() {
        return minOrderValue;
    }

    public void setMinOrderValue(BigDecimal minOrderValue) {
        this.minOrderValue = minOrderValue;
    }

    public BigDecimal getMaxDiscountAmount() {
        return maxDiscountAmount;
    }

    public void setMaxDiscountAmount(BigDecimal maxDiscountAmount) {
        this.maxDiscountAmount = maxDiscountAmount;
    }

    public String getEndDate() {
        return endDate;
    }

    public void setEndDate(String endDate) {
        this.endDate = endDate;
    }

    public int getMaxUsage() {
        return maxUsage;
    }

    public void setMaxUsage(int maxUsage) {
        this.maxUsage = maxUsage;
    }

    public int getUsedCount() {
        return usedCount;
    }

    public void setUsedCount(int usedCount) {
        this.usedCount = usedCount;
    }

    public String getStartDate() {
        return startDate;
    }

    public void setStartDate(String startDate) {
        this.startDate = startDate;
    }

    public boolean isActive() {
        return isActive;
    }

    public void setActive(boolean active) {
        isActive = active;
    }

    public boolean isPrivate() {
        return isPrivate;
    }

    public void setPrivate(boolean aPrivate) {
        isPrivate = aPrivate;
    }
}