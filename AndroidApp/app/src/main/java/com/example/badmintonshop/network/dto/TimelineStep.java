package com.example.badmintonshop.network.dto;

import com.google.gson.annotations.SerializedName;

public class TimelineStep {
    @SerializedName("status")
    private String status;
    @SerializedName("title")
    private String title;
    @SerializedName("timestamp")
    private String timestamp;
    @SerializedName("isCompleted")
    private boolean isCompleted;

    // Getters
    public String getStatus() { return status; }
    public String getTitle() { return title; }
    public String getTimestamp() { return timestamp; }
    public boolean isCompleted() { return isCompleted; }
}