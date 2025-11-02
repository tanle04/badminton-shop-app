package com.example.badmintonshop.network.dto;

import java.util.List;

/**
 * Response khi lấy danh sách tin nhắn
 */
public class MessagesListResponse {
    private String conversation_id;
    private List<SupportMessage> messages;

    public String getConversationId() {
        return conversation_id;
    }

    public void setConversationId(String conversation_id) {
        this.conversation_id = conversation_id;
    }

    public List<SupportMessage> getMessages() {
        return messages;
    }

    public void setMessages(List<SupportMessage> messages) {
        this.messages = messages;
    }
}