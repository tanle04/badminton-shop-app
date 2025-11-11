package com.example.badmintonshop.network.dto;

import com.google.gson.annotations.SerializedName;

/**
 * ✅ FIXED VERSION - Includes customer_id
 */
public class SendMessageRequest {

    @SerializedName("customer_id")
    private int customer_id;

    @SerializedName("conversation_id")
    private String conversation_id;

    @SerializedName("message")
    private String message;

    /**
     * ✅ Constructor với customer_id
     */
    public SendMessageRequest(int customerId, String conversationId, String message) {
        this.customer_id = customerId;
        this.conversation_id = conversationId;
        this.message = message;
    }

    // Getters
    public int getCustomerId() {
        return customer_id;
    }

    public String getConversationId() {
        return conversation_id;
    }

    public String getMessage() {
        return message;
    }

    // Setters
    public void setCustomerId(int customer_id) {
        this.customer_id = customer_id;
    }

    public void setConversationId(String conversation_id) {
        this.conversation_id = conversation_id;
    }

    public void setMessage(String message) {
        this.message = message;
    }
}