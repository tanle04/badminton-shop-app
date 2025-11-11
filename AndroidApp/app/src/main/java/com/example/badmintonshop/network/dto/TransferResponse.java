package com.example.badmintonshop.network.dto;

public class TransferResponse {
    private boolean success;
    private String message;
    private String new_conversation_id;  // ✅ THÊM FIELD NÀY
    private NewEmployee new_employee;

    public boolean isSuccess() {
        return success;
    }

    public void setSuccess(boolean success) {
        this.success = success;
    }

    public String getMessage() {
        return message;
    }

    public void setMessage(String message) {
        this.message = message;
    }

    // ✅ THÊM GETTER/SETTER
    public String getNewConversationId() {
        return new_conversation_id;
    }

    public void setNewConversationId(String new_conversation_id) {
        this.new_conversation_id = new_conversation_id;
    }

    public NewEmployee getNewEmployee() {
        return new_employee;
    }

    public void setNewEmployee(NewEmployee new_employee) {
        this.new_employee = new_employee;
    }

    public static class NewEmployee {
        private int employeeID;
        private String fullName;
        private String email;
        private String img_url;

        public int getEmployeeID() {
            return employeeID;
        }

        public void setEmployeeID(int employeeID) {
            this.employeeID = employeeID;
        }

        public String getFullName() {
            return fullName;
        }

        public void setFullName(String fullName) {
            this.fullName = fullName;
        }

        public String getEmail() {
            return email;
        }

        public void setEmail(String email) {
            this.email = email;
        }

        public String getImgUrl() {
            return img_url;
        }

        public void setImgUrl(String img_url) {
            this.img_url = img_url;
        }
    }
}