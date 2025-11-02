package com.example.badmintonshop.network.dto;

/**
 * Model của một tin nhắn support
 */
public class SupportMessage {
    private int id;
    private String sender_type;
    private String message;
    private String attachment_path;
    private String attachment_name;
    private String created_at;
    private Sender sender;

    public int getId() {
        return id;
    }

    public void setId(int id) {
        this.id = id;
    }

    public String getSenderType() {
        return sender_type;
    }

    public void setSenderType(String sender_type) {
        this.sender_type = sender_type;
    }

    public String getMessage() {
        return message;
    }

    public void setMessage(String message) {
        this.message = message;
    }

    public String getAttachmentPath() {
        return attachment_path;
    }

    public void setAttachmentPath(String attachment_path) {
        this.attachment_path = attachment_path;
    }

    public String getAttachmentName() {
        return attachment_name;
    }

    public void setAttachmentName(String attachment_name) {
        this.attachment_name = attachment_name;
    }

    public String getCreatedAt() {
        return created_at;
    }

    public void setCreatedAt(String created_at) {
        this.created_at = created_at;
    }

    public Sender getSender() {
        return sender;
    }

    public void setSender(Sender sender) {
        this.sender = sender;
    }

    public boolean isFromCustomer() {
        return "customer".equals(sender_type);
    }

    public boolean isFromEmployee() {
        return "employee".equals(sender_type);
    }

    /**
     * Sender info (nested class)
     */
    public static class Sender {
        private String fullName;
        private String type;
        private String img_url;
        private String role;

        public String getFullName() {
            return fullName;
        }

        public void setFullName(String fullName) {
            this.fullName = fullName;
        }

        public String getType() {
            return type;
        }

        public void setType(String type) {
            this.type = type;
        }

        public String getImgUrl() {
            return img_url;
        }

        public void setImgUrl(String img_url) {
            this.img_url = img_url;
        }

        public String getRole() {
            return role;
        }

        public void setRole(String role) {
            this.role = role;
        }
    }
}