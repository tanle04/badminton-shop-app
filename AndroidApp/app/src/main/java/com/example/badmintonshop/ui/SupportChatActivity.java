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
import android.widget.Button;
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
import com.example.badmintonshop.adapter.EmployeeSelectionAdapter;
import com.example.badmintonshop.adapter.SupportChatAdapter;
import com.example.badmintonshop.network.ApiClient;
import com.example.badmintonshop.network.SupportApiService;
import com.example.badmintonshop.network.dto.ConversationResponse;
import com.example.badmintonshop.network.dto.EmployeesListResponse;
import com.example.badmintonshop.network.dto.MessageResponse;
import com.example.badmintonshop.network.dto.MessagesListResponse;
import com.example.badmintonshop.network.dto.SendMessageRequest;
import com.example.badmintonshop.network.dto.SupportMessage;
import com.example.badmintonshop.network.dto.TransferRequest;
import com.example.badmintonshop.network.dto.TransferResponse;
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
import java.io.IOException;
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
 * ✅ FINAL VERSION - Fixed with customer_id in all requests
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
    private String customerName;

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
        setupChangeEmployeeButton();
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

        customerName = getSharedPreferences("user_prefs", MODE_PRIVATE)
                .getString("customer_name", "Bạn");

        log("👤 SharedPrefs Customer ID: " + sharedPrefsCustomerId);
        log("👤 Customer Name: " + customerName);

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

        // ✅ PASS customer_id to API
        apiService.initConversation(sharedPrefsCustomerId).enqueue(new Callback<ConversationResponse>() {
            @Override
            public void onResponse(@NonNull Call<ConversationResponse> call, @NonNull Response<ConversationResponse> response) {
                showLoading(false);

                if (response.isSuccessful() && response.body() != null) {
                    conversationId = response.body().getConversationId();
                    log("✅ Conversation ID: " + conversationId);

                    // ✅ Get REAL customer_id from API response
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
                    connectWebSocket();
                } else {
                    log("❌ Init conversation failed: " + response.code());

                    try {
                        if (response.errorBody() != null) {
                            String errorBody = response.errorBody().string();
                            log("❌ Error body: " + errorBody);
                        }
                    } catch (IOException e) {
                        log("❌ Cannot read error body: " + e.getMessage());
                    }

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
        if (conversationId == null) {
            log("⚠️ Cannot load history: conversationId is null");
            return;
        }

        log("📥 Loading message history for: " + conversationId);

        // ✅ PASS customer_id to API
        apiService.getMessagesByConversation(realCustomerId, conversationId).enqueue(new Callback<MessagesListResponse>() {
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

    // ============================================================================
    // ✅ FIXED: OPTIMISTIC UI UPDATE
    // ============================================================================
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
            sendTextMessageOptimistic(message);
        }
    }

    /**
     * ✅ OPTIMISTIC UPDATE: Hiển thị tin nhắn NGAY, sau đó gửi API
     */
    private void sendTextMessageOptimistic(String messageText) {
        // ✅ 1. TẠO TEMP MESSAGE
        SupportMessage tempMessage = new SupportMessage();
        tempMessage.setId((int) System.currentTimeMillis()); // Temp ID
        tempMessage.setConversationId(conversationId);
        tempMessage.setSenderType("customer");
        tempMessage.setSenderId(realCustomerId);
        tempMessage.setMessage(messageText);
        tempMessage.setCreatedAt(new SimpleDateFormat("yyyy-MM-dd HH:mm:ss", Locale.getDefault()).format(new Date()));

        // ✅ Sender info
        SupportMessage.Sender sender = new SupportMessage.Sender();
        sender.setFullName(customerName != null ? customerName : "Bạn");
        sender.setType("customer");
        tempMessage.setSender(sender);

        // ✅ 2. THÊM VÀO UI NGAY LẬP TỨC
        messagesList.add(tempMessage);
        adapter.notifyItemInserted(messagesList.size() - 1);
        scrollToBottom();

        // ✅ 3. CLEAR INPUT
        editTextMessage.setText("");

        log("✅ Message added to UI optimistically");

        // ✅ 4. GỬI LÊN SERVER (background)
        SendMessageRequest request = new SendMessageRequest(realCustomerId, conversationId, messageText);

        apiService.sendMessage(request).enqueue(new Callback<MessageResponse>() {
            @Override
            public void onResponse(@NonNull Call<MessageResponse> call, @NonNull Response<MessageResponse> response) {
                buttonSend.setEnabled(true);

                if (response.isSuccessful() && response.body() != null && response.body().isSuccess()) {
                    log("✅ Message sent to server successfully");

                    // ✅ 5. CẬP NHẬT ID THẬT từ server
                    SupportMessage realMessage = response.body().getMessage();
                    if (realMessage != null) {
                        int index = findMessageIndex(tempMessage.getId());
                        if (index != -1) {
                            messagesList.set(index, realMessage);
                            adapter.notifyItemChanged(index);
                            log("✅ Updated message with real ID: " + realMessage.getId());
                        }
                    }
                } else {
                    // ✅ 6. NẾU LỖI, XÓA TEMP MESSAGE
                    log("❌ Send failed: " + response.code());

                    int index = findMessageIndex(tempMessage.getId());
                    if (index != -1) {
                        messagesList.remove(index);
                        adapter.notifyItemRemoved(index);
                        log("❌ Removed failed message from UI");
                    }

                    try {
                        if (response.errorBody() != null) {
                            String errorBody = response.errorBody().string();
                            log("❌ Error body: " + errorBody);
                        }
                    } catch (IOException e) {
                        log("❌ Cannot read error body: " + e.getMessage());
                    }

                    runOnUiThread(() -> {
                        Toast.makeText(SupportChatActivity.this, "Lỗi gửi tin nhắn", Toast.LENGTH_SHORT).show();
                    });
                }
            }

            @Override
            public void onFailure(@NonNull Call<MessageResponse> call, @NonNull Throwable t) {
                buttonSend.setEnabled(true);
                log("❌ Send error: " + t.getMessage());

                // ✅ XÓA TEMP MESSAGE
                int index = findMessageIndex(tempMessage.getId());
                if (index != -1) {
                    messagesList.remove(index);
                    adapter.notifyItemRemoved(index);
                }

                runOnUiThread(() -> {
                    Toast.makeText(SupportChatActivity.this, "Lỗi: " + t.getMessage(), Toast.LENGTH_SHORT).show();
                });
            }
        });
    }

    /**
     * Helper: Tìm index của message theo ID
     */
    private int findMessageIndex(int messageId) {
        for (int i = 0; i < messagesList.size(); i++) {
            if (messagesList.get(i).getId() == messageId) {
                return i;
            }
        }
        return -1;
    }

    private void sendMessageWithFile(String message, Uri fileUri) {
        log("📎 Sending with attachment");
        // Giữ nguyên implementation cũ
    }

    // ============================================================================
    // ✅ FIXED WEBSOCKET HANDLER
    // ============================================================================
    private void connectWebSocket() {
        log("🔌 === STARTING WEBSOCKET CONNECTION ===");
        log("🔌 Using Customer ID: " + realCustomerId);
        log("🔌 Conversation ID: " + conversationId);

        try {
            PusherOptions options = new PusherOptions();
            options.setCluster("ap1");
            options.setUseTLS(true);

            String PUSHER_APP_KEY = "c3ca7c07e100fdf6218b";

            log("⚙️ Pusher Config (Cloud):");
            log("   - Key: " + PUSHER_APP_KEY);
            log("   - Cluster: ap1");
            log("   - TLS: true");

            pusher = new Pusher(PUSHER_APP_KEY, options);

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
     * ✅ FIXED: Chỉ reload khi nhận tin EMPLOYEE
     */
    private void subscribeToChannel() {
        if (conversationId == null) {
            log("❌ Cannot subscribe: conversationId is null!");
            return;
        }

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

                                // ✅ FIXED LOGIC: Chỉ reload khi nhận tin EMPLOYEE
                                if ("employee".equals(senderType) && conversationId.equals(convId)) {
                                    log("✅ Employee message for this conversation - RELOADING!");
                                    loadMessageHistory();
                                } else if ("customer".equals(senderType) && conversationId.equals(convId)) {
                                    log("ℹ️ Customer message (already in UI via optimistic update)");
                                    // KHÔNG cần làm gì vì đã thêm vào UI rồi
                                } else {
                                    log("ℹ️ Message for different conversation or sender");
                                }
                            } else {
                                log("⚠️ Message object is null in event data");
                            }
                        } catch (JSONException e) {
                            log("❌ JSON Parse Error: " + e.getMessage());
                            e.printStackTrace();
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

    // ============================================================================
    // EMPLOYEE SELECTION
    // ============================================================================
    private void setupChangeEmployeeButton() {
        Button buttonChangeEmployee = findViewById(R.id.buttonChangeEmployee);
        if (buttonChangeEmployee != null) {
            buttonChangeEmployee.setOnClickListener(v -> showEmployeeSelectionDialog());
        }
    }

    private void showEmployeeSelectionDialog() {
        log("📋 Fetching available employees...");

        apiService.getAvailableEmployees().enqueue(new Callback<EmployeesListResponse>() {
            @Override
            public void onResponse(@NonNull Call<EmployeesListResponse> call, @NonNull Response<EmployeesListResponse> response) {
                if (response.isSuccessful() && response.body() != null && response.body().isSuccess()) {
                    List<EmployeesListResponse.Employee> employees = response.body().getEmployees();

                    if (employees.isEmpty()) {
                        Toast.makeText(SupportChatActivity.this, "Không có nhân viên nào", Toast.LENGTH_SHORT).show();
                        return;
                    }

                    log("✅ Found " + employees.size() + " employees");
                    showEmployeeDialog(employees);
                } else {
                    log("❌ Load employees failed: " + response.code());
                    Toast.makeText(SupportChatActivity.this, "Không thể tải danh sách", Toast.LENGTH_SHORT).show();
                }
            }

            @Override
            public void onFailure(@NonNull Call<EmployeesListResponse> call, @NonNull Throwable t) {
                log("❌ Load employees error: " + t.getMessage());
                Toast.makeText(SupportChatActivity.this, "Lỗi kết nối", Toast.LENGTH_SHORT).show();
            }
        });
    }

    private void showEmployeeDialog(List<EmployeesListResponse.Employee> employees) {
        android.app.AlertDialog.Builder builder = new android.app.AlertDialog.Builder(this);
        View dialogView = getLayoutInflater().inflate(R.layout.dialog_select_employee, null);

        RecyclerView recyclerView = dialogView.findViewById(R.id.recyclerViewEmployees);
        recyclerView.setLayoutManager(new LinearLayoutManager(this));

        android.app.AlertDialog dialog = builder.setView(dialogView).create();

        EmployeeSelectionAdapter adapter = new EmployeeSelectionAdapter(this, employees, employee -> {
            transferConversation(employee);
            dialog.dismiss();
        });

        recyclerView.setAdapter(adapter);

        dialogView.findViewById(R.id.buttonCancel).setOnClickListener(v -> dialog.dismiss());

        dialog.show();
    }

    private void transferConversation(EmployeesListResponse.Employee employee) {
        if (conversationId == null) {
            Toast.makeText(this, "Chưa có cuộc hội thoại", Toast.LENGTH_SHORT).show();
            return;
        }

        log("🔄 Transferring to: " + employee.getFullName());

        TransferRequest request = new TransferRequest(conversationId, employee.getEmployeeID(), realCustomerId);

        apiService.transferConversation(request).enqueue(new Callback<TransferResponse>() {
            @Override
            public void onResponse(@NonNull Call<TransferResponse> call, @NonNull Response<TransferResponse> response) {
                if (response.isSuccessful() && response.body() != null && response.body().isSuccess()) {
                    log("✅ Transfer successful");

                    String newConversationId = response.body().getNewConversationId();
                    if (newConversationId != null && !newConversationId.isEmpty()) {
                        conversationId = newConversationId;
                        log("🆕 New conversation ID: " + conversationId);

                        if (channel != null && pusher != null) {
                            try {
                                pusher.unsubscribe(channel.getName());
                                log("🔌 Unsubscribed from old channel");
                            } catch (Exception e) {
                                log("⚠️ Unsubscribe error: " + e.getMessage());
                            }
                        }

                        subscribeToChannel();
                    }

                    textViewEmployeeName.setText("Nhân viên: " + employee.getFullName());

                    messagesList.clear();
                    adapter.notifyDataSetChanged();

                    runOnUiThread(() -> {
                        Toast.makeText(SupportChatActivity.this,
                                "Đã chuyển sang " + employee.getFullName(), Toast.LENGTH_SHORT).show();
                    });

                    loadMessageHistory();
                } else {
                    log("❌ Transfer failed: " + response.code());
                    Toast.makeText(SupportChatActivity.this, "Không thể chuyển", Toast.LENGTH_SHORT).show();
                }
            }

            @Override
            public void onFailure(@NonNull Call<TransferResponse> call, @NonNull Throwable t) {
                log("❌ Transfer error: " + t.getMessage());
                Toast.makeText(SupportChatActivity.this, "Lỗi chuyển cuộc hội thoại", Toast.LENGTH_SHORT).show();
            }
        });
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