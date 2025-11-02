package com.example.badmintonshop.network;

import com.example.badmintonshop.network.dto.ConversationResponse;
import com.example.badmintonshop.network.dto.MessageResponse;
import com.example.badmintonshop.network.dto.MessagesListResponse;
import com.example.badmintonshop.network.dto.SendMessageRequest;
import com.example.badmintonshop.network.dto.UnreadCountResponse;

import okhttp3.MultipartBody;
import okhttp3.RequestBody;
import retrofit2.Call;
import retrofit2.http.Body;
import retrofit2.http.GET;
import retrofit2.http.Multipart;
import retrofit2.http.POST;
import retrofit2.http.Part;

/**
 * Support API Service Interface
 *
 * Sử dụng support-simple.php (KHÔNG CẦN .htaccess)
 */
public interface SupportApiService {

    /**
     * Khởi tạo hoặc lấy conversation hiện có
     */
    @POST("support-simple.php?action=init")
    Call<ConversationResponse> initConversation();

    /**
     * Gửi tin nhắn TEXT (không có file đính kèm)
     */
    @POST("support-simple.php?action=send")
    Call<MessageResponse> sendMessage(@Body SendMessageRequest request);

    /**
     * Gửi tin nhắn có FILE đính kèm
     */
    @Multipart
    @POST("support-simple.php?action=send")
    Call<MessageResponse> sendMessageWithAttachment(
            @Part("conversation_id") RequestBody conversationId,
            @Part("message") RequestBody message,
            @Part MultipartBody.Part attachment
    );

    /**
     * Lấy lịch sử tin nhắn
     */
    @GET("support-simple.php?action=history")
    Call<MessagesListResponse> getMessages();

    /**
     * Đếm số tin nhắn chưa đọc từ nhân viên
     */
    @GET("support-simple.php?action=unread-count")
    Call<UnreadCountResponse> getUnreadCount();
}