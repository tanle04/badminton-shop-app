package com.example.badmintonshop.network.dto;

import com.google.gson.annotations.SerializedName;

public class TransferRequest {

    @SerializedName("conversation_id")
    private String conversation_id;

    @SerializedName("new_employee_id")
    private int new_employee_id;

    // ✅ BƯỚC 1: THÊM TRƯỜNG NÀY
    @SerializedName("customer_id")
    private int customer_id;

    /**
     * ✅ BƯỚC 2: CẬP NHẬT CONSTRUCTOR
     * Thêm customerId
     */
    public TransferRequest(String conversationId, int newEmployeeId, int customerId) {
        this.conversation_id = conversationId;
        this.new_employee_id = newEmployeeId;
        this.customer_id = customerId; // <-- Thêm dòng này
    }

    // Getters and Setters
    public String getConversationId() {
        return conversation_id;
    }

    public void setConversationId(String conversation_id) {
        this.conversation_id = conversation_id;
    }

    public int getNewEmployeeId() {
        return new_employee_id;
    }

    public void setNewEmployeeId(int new_employee_id) {
        this.new_employee_id = new_employee_id;
    }

    // ✅ BƯỚC 3: THÊM GETTER/SETTER CHO customer_id
    public int getCustomerId() {
        return customer_id;
    }

    public void setCustomerId(int customer_id) {
        this.customer_id = customer_id;
    }
}