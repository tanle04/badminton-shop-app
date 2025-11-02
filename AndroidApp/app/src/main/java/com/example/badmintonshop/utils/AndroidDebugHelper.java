package com.example.badmintonshop.utils;

import android.util.Log;
import java.text.SimpleDateFormat;
import java.util.Date;
import java.util.Locale;

public class AndroidDebugHelper {
    private static final String TAG = "SupportChatDebug";

    public static void log(String message) {
        String timestamp = new SimpleDateFormat("HH:mm:ss", Locale.getDefault())
                .format(new Date());
        String formatted = String.format("[%s] %s", timestamp, message);
        Log.d(TAG, formatted);
    }

    public static void logPusherState(String state) {
        log("🔌 Pusher State: " + state);
    }

    public static void logChannelSubscription(String channel) {
        log("📡 Subscribed to: " + channel);
    }

    public static void logMessageReceived(String from, String message) {
        log(String.format("📩 Message from %s: %s", from, message));
    }

    public static void logError(String error) {
        log("❌ Error: " + error);
    }
}