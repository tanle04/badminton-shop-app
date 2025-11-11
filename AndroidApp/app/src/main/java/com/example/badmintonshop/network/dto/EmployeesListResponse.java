package com.example.badmintonshop.network.dto;

import java.util.List;

public class EmployeesListResponse {
    private boolean success;
    private List<Employee> employees;

    public boolean isSuccess() {
        return success;
    }

    public void setSuccess(boolean success) {
        this.success = success;
    }

    public List<Employee> getEmployees() {
        return employees;
    }

    public void setEmployees(List<Employee> employees) {
        this.employees = employees;
    }

    public static class Employee {
        private int employeeID;
        private String fullName;
        private String email;
        private String img_url;
        private String role;
        private boolean isOnline;

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

        public String getRole() {
            return role;
        }

        public void setRole(String role) {
            this.role = role;
        }

        public boolean isOnline() {
            return isOnline;
        }

        public void setOnline(boolean online) {
            isOnline = online;
        }
    }
}