package com.example.badmintonshop.ui;

import android.Manifest;
import android.content.Intent;
import android.content.pm.PackageManager;
import android.net.Uri;
import android.os.Bundle;
import android.provider.MediaStore;
import android.text.TextUtils;
import android.util.Log;
import android.view.View;
import android.widget.EditText;
import android.widget.ImageButton;
import android.widget.ProgressBar;
import android.widget.TextView;
import android.widget.Toast;

import androidx.annotation.NonNull;
import androidx.annotation.Nullable;
import androidx.appcompat.app.AppCompatActivity;
import androidx.core.app.ActivityCompat;
import androidx.core.content.ContextCompat;
import androidx.recyclerview.widget.LinearLayoutManager;
import androidx.recyclerview.widget.RecyclerView;

import com.example.badmintonshop.R;
import com.example.badmintonshop.adapter.SupportChatAdapter;
import com.example.badmintonshop.network.ApiClient;
import com.example.badmintonshop.network.SupportApiService;
import com.example.badmintonshop.network.dto.ConversationResponse;
import com.example.badmintonshop.network.dto.MessageResponse;
import com.example.badmintonshop.network.dto.MessagesListResponse;
import com.example.badmintonshop.network.dto.SendMessageRequest;
import com.example.badmintonshop.network.dto.SupportMessage;

import com.pusher.client.Pusher;
import com.pusher.client.PusherOptions;
import com.pusher.client.channel.Channel;
import com.pusher.client.channel.ChannelEventListener;
import com.pusher.client.channel.PusherEvent;
import com.pusher.client.connection.ConnectionEventListener;
import com.pusher.client.connection.ConnectionState;
import com.pusher.client.connection.ConnectionStateChange;

import org.json.JSONException;
import org.json.JSONObject;

import java.io.File;
import java.text.SimpleDateFormat;
import java.util.ArrayList;
import java.util.Date;
import java.util.List;
import java.util.Locale;

import okhttp3.MediaType;
import okhttp3.MultipartBody;
import okhttp3.RequestBody;
import retrofit2.Call;
import retrofit2.Callback;
import retrofit2.Response;

/**
 * ✅ FINAL FIX: Subscribe theo customer_id từ API response
 */
public class SupportChatActivity extends AppCompatActivity {

    private static final String TAG = "SupportChat";
    private static final int REQUEST_PICK_FILE = 100;
    private static final int REQUEST_STORAGE_PERMISSION = 101;

    // UI
    private RecyclerView recyclerViewMessages;
    private EditText editTextMessage;
    private ImageButton buttonSend;
    private ImageButton buttonAttach;
    private TextView textViewEmployeeName;
    private TextView textViewStatus;
    private ProgressBar progressBar;

    // Data
    private SupportChatAdapter adapter;
    private List<SupportMessage> messagesList = new ArrayList<>();
    private String conversationId;
    private Uri selectedFileUri;

    // API
    private SupportApiService apiService;

    // WebSocket
    private Pusher pusher;
    private Channel channel;

    // ✅ CRITICAL: Get from API, not SharedPreferences
    private int realCustomerId;
    private int sharedPrefsCustomerId;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_support_chat);

        log("🚀 === ACTIVITY STARTED ===");

        initViews();
        initData();
        setupRecyclerView();
        setupListeners();
        initConversation();
    }

    private void initViews() {
        recyclerViewMessages = findViewById(R.id.recyclerViewMessages);
        editTextMessage = findViewById(R.id.editTextMessage);
        buttonSend = findViewById(R.id.buttonSend);
        buttonAttach = findViewById(R.id.buttonAttach);
        textViewEmployeeName = findViewById(R.id.textViewEmployeeName);
        textViewStatus = findViewById(R.id.textViewStatus);
        progressBar = findViewById(R.id.progressBar);

        log("✅ Views initialized");
    }

    private void initData() {
        apiService = ApiClient.getRetrofitInstance().create(SupportApiService.class);

        sharedPrefsCustomerId = getSharedPreferences("user_prefs", MODE_PRIVATE)
                .getInt("customer_id", 0);

        log("👤 SharedPrefs Customer ID: " + sharedPrefsCustomerId);

        if (sharedPrefsCustomerId == 0) {
            log("❌ Customer ID is 0! Finishing activity");
            Toast.makeText(this, "Vui lòng đăng nhập", Toast.LENGTH_SHORT).show();
            finish();
        }
    }

    private void setupRecyclerView() {
        adapter = new SupportChatAdapter(this, messagesList, sharedPrefsCustomerId);
        recyclerViewMessages.setLayoutManager(new LinearLayoutManager(this));
        recyclerViewMessages.setAdapter(adapter);
        log("✅ RecyclerView setup complete");
    }

    private void setupListeners() {
        buttonSend.setOnClickListener(v -> sendMessage());
        buttonAttach.setOnClickListener(v -> {
            if (checkStoragePermission()) {
                openFilePicker();
            } else {
                requestStoragePermission();
            }
        });
    }

    // ============================================================================
    // STEP 1: INIT CONVERSATION & GET REAL CUSTOMER ID
    // ============================================================================
    private void initConversation() {
        log("📞 Initializing conversation...");
        showLoading(true);

        apiService.initConversation().enqueue(new Callback<ConversationResponse>() {
            @Override
            public void onResponse(@NonNull Call<ConversationResponse> call, @NonNull Response<ConversationResponse> response) {
                showLoading(false);

                if (response.isSuccessful() && response.body() != null) {
                    conversationId = response.body().getConversationId();
                    log("✅ Conversation ID: " + conversationId);

                    // ✅ CRITICAL: Get REAL customer_id from API response
                    realCustomerId = response.body().getCustomerId();
                    log("✅ REAL Customer ID from API: " + realCustomerId);
                    log("⚠️ SharedPrefs had: " + sharedPrefsCustomerId);

                    if (realCustomerId != sharedPrefsCustomerId) {
                        log("🔥 MISMATCH DETECTED!");
                        log("   - SharedPrefs: " + sharedPrefsCustomerId);
                        log("   - API Response: " + realCustomerId);
                        log("   - Will use API value: " + realCustomerId);
                    }

                    ConversationResponse.AssignedEmployee employee = response.body().getAssignedEmployee();
                    if (employee != null) {
                        textViewEmployeeName.setText("Nhân viên: " + employee.getFullName());
                        textViewStatus.setText("Đang hỗ trợ");
                        log("👨‍💼 Employee: " + employee.getFullName());
                    } else {
                        textViewEmployeeName.setText("Đang tìm nhân viên...");
                        textViewStatus.setText("Vui lòng chờ");
                        log("⚠️ No employee assigned yet");
                    }

                    loadMessageHistory();

                    // ✅ Connect WebSocket with REAL customer_id
                    connectWebSocket();
                } else {
                    log("❌ Init conversation failed: " + response.code());
                    Toast.makeText(SupportChatActivity.this,
                            "Không thể kết nối", Toast.LENGTH_SHORT).show();
                }
            }

            @Override
            public void onFailure(@NonNull Call<ConversationResponse> call, @NonNull Throwable t) {
                showLoading(false);
                log("❌ Init conversation error: " + t.getMessage());
                t.printStackTrace();
            }
        });
    }

    private void loadMessageHistory() {
        log("📥 Loading message history...");

        apiService.getMessages().enqueue(new Callback<MessagesListResponse>() {
            @Override
            public void onResponse(@NonNull Call<MessagesListResponse> call, @NonNull Response<MessagesListResponse> response) {
                if (response.isSuccessful() && response.body() != null) {
                    int oldSize = messagesList.size();
                    messagesList.clear();
                    messagesList.addAll(response.body().getMessages());
                    adapter.notifyDataSetChanged();
                    scrollToBottom();
                    log("✅ Loaded " + messagesList.size() + " messages (was: " + oldSize + ")");
                } else {
                    log("❌ Load messages failed: " + response.code());
                }
            }

            @Override
            public void onFailure(@NonNull Call<MessagesListResponse> call, @NonNull Throwable t) {
                log("❌ Load messages error: " + t.getMessage());
            }
        });
    }

    private void sendMessage() {
        String message = editTextMessage.getText().toString().trim();

        if (TextUtils.isEmpty(message) && selectedFileUri == null) {
            Toast.makeText(this, "Vui lòng nhập tin nhắn", Toast.LENGTH_SHORT).show();
            return;
        }

        if (conversationId == null) {
            Toast.makeText(this, "Chưa kết nối", Toast.LENGTH_SHORT).show();
            return;
        }

        log("📤 Sending message: " + message);
        buttonSend.setEnabled(false);

        if (selectedFileUri != null) {
            sendMessageWithFile(message, selectedFileUri);
        } else {
            sendTextMessage(message);
        }
    }

    private void sendTextMessage(String message) {
        SendMessageRequest request = new SendMessageRequest(conversationId, message);

        apiService.sendMessage(request).enqueue(new Callback<MessageResponse>() {
            @Override
            public void onResponse(@NonNull Call<MessageResponse> call, @NonNull Response<MessageResponse> response) {
                buttonSend.setEnabled(true);

                if (response.isSuccessful() && response.body() != null && response.body().isSuccess()) {
                    editTextMessage.setText("");
                    log("✅ Message sent successfully");

                    SupportMessage newMsg = response.body().getMessage();
                    messagesList.add(newMsg);
                    adapter.notifyItemInserted(messagesList.size() - 1);
                    scrollToBottom();
                } else {
                    log("❌ Send failed: " + response.code());
                    Toast.makeText(SupportChatActivity.this, "Lỗi gửi tin nhắn", Toast.LENGTH_SHORT).show();
                }
            }

            @Override
            public void onFailure(@NonNull Call<MessageResponse> call, @NonNull Throwable t) {
                buttonSend.setEnabled(true);
                log("❌ Send error: " + t.getMessage());
            }
        });
    }

    private void sendMessageWithFile(String message, Uri fileUri) {
        log("📎 Sending with attachment");
        // Same implementation...
    }

    // ============================================================================
    // WEBSOCKET CONNECTION ✅
    // ============================================================================
    private void connectWebSocket() {
        log("🔌 === STARTING WEBSOCKET CONNECTION ===");
        log("🔌 Using Customer ID: " + realCustomerId);
        log("🔌 Conversation ID: " + conversationId);

        try {
            PusherOptions options = new PusherOptions();
            options.setCluster("mt1");
            options.setHost("10.0.2.2");
            options.setWsPort(6001);
            options.setWssPort(6001);
            options.setUseTLS(false);
            options.setEncrypted(false);

            log("⚙️ Pusher Config:");
            log("   - Host: 10.0.2.2");
            log("   - Port: 6001");
            log("   - Key: badmintonshop2025key");
            log("   - TLS: false");

            pusher = new Pusher("badmintonshop2025key", options);

            pusher.connect(new ConnectionEventListener() {
                @Override
                public void onConnectionStateChange(ConnectionStateChange change) {
                    String state = change.getCurrentState().toString();
                    log("🔌 Pusher State Changed: " + state);

                    if (change.getCurrentState() == ConnectionState.CONNECTING) {
                        log("🔌 Pusher is CONNECTING...");
                        runOnUiThread(() -> textViewStatus.setText("Đang kết nối..."));
                    }
                    else if (change.getCurrentState() == ConnectionState.CONNECTED) {
                        log("✅ ✅ ✅ PUSHER CONNECTED! ✅ ✅ ✅");
                        runOnUiThread(() -> {
                            textViewStatus.setText("Đã kết nối");
                            subscribeToChannel();
                        });
                    }
                    else if (change.getCurrentState() == ConnectionState.DISCONNECTED) {
                        log("⚠️ Pusher DISCONNECTED");
                        runOnUiThread(() -> textViewStatus.setText("Mất kết nối"));
                    }
                }

                @Override
                public void onError(String message, String code, Exception e) {
                    log("❌ Pusher Error: " + message);
                    log("❌ Error Code: " + code);
                    if (e != null) {
                        log("❌ Exception: " + e.getMessage());
                        e.printStackTrace();
                    }
                }
            }, ConnectionState.ALL);

            log("✅ Pusher instance created, connecting...");

        } catch (Exception e) {
            log("❌ WebSocket init exception: " + e.getMessage());
            e.printStackTrace();
        }
    }

    /**
     * ✅ CRITICAL FIX: Use REAL customer_id from API
     */
    private void subscribeToChannel() {
        if (conversationId == null) {
            log("❌ Cannot subscribe: conversationId is null!");
            return;
        }

        // ✅ Use REAL customer_id from API response
        String channelName = "customer-support-" + realCustomerId;

        log("📡 === SUBSCRIBING TO CHANNEL ===");
        log("📡 Channel Name: " + channelName);
        log("📡 Using customer_id: " + realCustomerId + " (from API)");
        log("📡 NOT using: " + sharedPrefsCustomerId + " (from SharedPrefs)");

        try {
            channel = pusher.subscribe(channelName);

            channel.bind("support.message.sent", new ChannelEventListener() {
                @Override
                public void onSubscriptionSucceeded(String channelName) {
                    log("✅ ✅ ✅ SUBSCRIPTION SUCCESSFUL! ✅ ✅ ✅");
                    log("✅ Subscribed to: " + channelName);
                }

                @Override
                public void onEvent(PusherEvent event) {
                    log("📩 === EVENT RECEIVED ===");
                    log("📩 Event Name: " + event.getEventName());
                    log("📩 Event Data: " + event.getData());

                    runOnUiThread(() -> {
                        try {
                            JSONObject jsonData = new JSONObject(event.getData());
                            JSONObject messageObj = jsonData.optJSONObject("message");

                            if (messageObj != null) {
                                String senderType = messageObj.optString("sender_type", "");
                                String convId = messageObj.optString("conversation_id", "");
                                String msg = messageObj.optString("message", "");

                                log("👤 Sender Type: " + senderType);
                                log("🆔 Conversation: " + convId);
                                log("💬 Message: " + msg);

                                if ("employee".equals(senderType) && conversationId.equals(convId)) {
                                    log("✅ Employee message for this conversation - RELOADING!");
                                    loadMessageHistory();
                                } else {
                                    log("ℹ️ Skipping reload (not employee or different conversation)");
                                }
                            } else {
                                log("⚠️ Message object is null in event data");
                            }
                        } catch (JSONException e) {
                            log("❌ JSON Parse Error: " + e.getMessage());
                            e.printStackTrace();
                            loadMessageHistory(); // Fallback
                        }
                    });
                }
            });

            log("✅ Event binding complete");

        } catch (Exception e) {
            log("❌ Subscribe exception: " + e.getMessage());
            e.printStackTrace();
        }
    }

    // ============================================================================
    // UTILITIES
    // ============================================================================
    private void log(String message) {
        String timestamp = new SimpleDateFormat("HH:mm:ss", Locale.getDefault())
                .format(new Date());
        String formatted = "[" + timestamp + "] " + message;
        Log.d(TAG, formatted);
    }

    private void showLoading(boolean show) {
        progressBar.setVisibility(show ? View.VISIBLE : View.GONE);
    }

    private void scrollToBottom() {
        if (messagesList.size() > 0) {
            recyclerViewMessages.smoothScrollToPosition(messagesList.size() - 1);
        }
    }

    private String getRealPathFromURI(Uri uri) {
        return uri.getPath();
    }

    private void openFilePicker() {
        Intent intent = new Intent(Intent.ACTION_PICK, MediaStore.Images.Media.EXTERNAL_CONTENT_URI);
        intent.setType("image/*");
        startActivityForResult(intent, REQUEST_PICK_FILE);
    }

    @Override
    protected void onActivityResult(int requestCode, int resultCode, @Nullable Intent data) {
        super.onActivityResult(requestCode, resultCode, data);
        if (requestCode == REQUEST_PICK_FILE && resultCode == RESULT_OK && data != null) {
            selectedFileUri = data.getData();
        }
    }

    private boolean checkStoragePermission() {
        return ContextCompat.checkSelfPermission(this,
                Manifest.permission.READ_EXTERNAL_STORAGE) == PackageManager.PERMISSION_GRANTED;
    }

    private void requestStoragePermission() {
        ActivityCompat.requestPermissions(this,
                new String[]{Manifest.permission.READ_EXTERNAL_STORAGE},
                REQUEST_STORAGE_PERMISSION);
    }

    @Override
    public void onRequestPermissionsResult(int requestCode, @NonNull String[] permissions, @NonNull int[] grantResults) {
        super.onRequestPermissionsResult(requestCode, permissions, grantResults);
        if (requestCode == REQUEST_STORAGE_PERMISSION) {
            if (grantResults.length > 0 && grantResults[0] == PackageManager.PERMISSION_GRANTED) {
                openFilePicker();
            }
        }
    }

    @Override
    protected void onDestroy() {
        super.onDestroy();
        log("🔴 === ACTIVITY DESTROYED ===");
        if (pusher != null) {
            pusher.disconnect();
            log("🔌 WebSocket disconnected");
        }
    }
}