package com.example.badmintonshop.network.dto;

/**
 * Response khi gửi tin nhắn
 */
public class MessageResponse {
    private boolean success;
    private SupportMessage message;
    private String error;

    public boolean isSuccess() {
        return success;
    }

    public void setSuccess(boolean success) {
        this.success = success;
    }

    public SupportMessage getMessage() {
        return message;
    }

    public void setMessage(SupportMessage message) {
        this.message = message;
    }

    public String getError() {
        return error;
    }

    public void setError(String error) {
        this.error = error;
    }
}