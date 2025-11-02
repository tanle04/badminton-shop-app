package com.example.badmintonshop.network.dto;

/**
 * Request body khi gửi tin nhắn (không có file)
 */
public class SendMessageRequest {
    private String conversation_id;
    private String message;

    public SendMessageRequest(String conversationId, String message) {
        this.conversation_id = conversationId;
        this.message = message;
    }

    public String getConversationId() {
        return conversation_id;
    }

    public void setConversationId(String conversation_id) {
        this.conversation_id = conversation_id;
    }

    public String getMessage() {
        return message;
    }

    public void setMessage(String message) {
        this.message = message;
    }
}