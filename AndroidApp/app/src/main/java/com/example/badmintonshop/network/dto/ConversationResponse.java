package com.example.badmintonshop.network.dto;

import com.google.gson.annotations.SerializedName;

public class ConversationResponse {

    @SerializedName("success")
    private boolean success;

    @SerializedName("conversation_id")
    private String conversationId;

    // ✅ CRITICAL: Add this field
    @SerializedName("customer_id")
    private int customerId;

    @SerializedName("assigned_employee")
    private AssignedEmployee assignedEmployee;

    @SerializedName("message")
    private String message;

    // Getters
    public boolean isSuccess() {
        return success;
    }

    public String getConversationId() {
        return conversationId;
    }

    // ✅ CRITICAL: Add this getter
    public int getCustomerId() {
        return customerId;
    }

    public AssignedEmployee getAssignedEmployee() {
        return assignedEmployee;
    }

    public String getMessage() {
        return message;
    }

    // Setters
    public void setSuccess(boolean success) {
        this.success = success;
    }

    public void setConversationId(String conversationId) {
        this.conversationId = conversationId;
    }

    public void setCustomerId(int customerId) {
        this.customerId = customerId;
    }

    public void setAssignedEmployee(AssignedEmployee assignedEmployee) {
        this.assignedEmployee = assignedEmployee;
    }

    public void setMessage(String message) {
        this.message = message;
    }

    // Inner class
    public static class AssignedEmployee {
        @SerializedName("employeeID")
        private int employeeID;

        @SerializedName("fullName")
        private String fullName;

        @SerializedName("email")
        private String email;

        @SerializedName("img_url")
        private String imgUrl;

        public int getEmployeeID() {
            return employeeID;
        }

        public String getFullName() {
            return fullName;
        }

        public String getEmail() {
            return email;
        }

        public String getImgUrl() {
            return imgUrl;
        }

        public void setEmployeeID(int employeeID) {
            this.employeeID = employeeID;
        }

        public void setFullName(String fullName) {
            this.fullName = fullName;
        }

        public void setEmail(String email) {
            this.email = email;
        }

        public void setImgUrl(String imgUrl) {
            this.imgUrl = imgUrl;
        }
    }
}