package com.example.badmintonshop.network;

import com.example.badmintonshop.network.dto.ConversationResponse;
import com.example.badmintonshop.network.dto.EmployeesListResponse;
import com.example.badmintonshop.network.dto.MessageResponse;
import com.example.badmintonshop.network.dto.MessagesListResponse;
import com.example.badmintonshop.network.dto.SendMessageRequest;
import com.example.badmintonshop.network.dto.TransferRequest;
import com.example.badmintonshop.network.dto.TransferResponse;
import com.example.badmintonshop.network.dto.UnreadCountResponse;

import okhttp3.MultipartBody;
import okhttp3.RequestBody;
import retrofit2.Call;
import retrofit2.http.Body;
import retrofit2.http.Field;
import retrofit2.http.FormUrlEncoded;
import retrofit2.http.GET;
import retrofit2.http.Multipart;
import retrofit2.http.POST;
import retrofit2.http.Part;
import retrofit2.http.Query;

/**
 * ✅ FIXED VERSION - All methods now include customer_id
 */
public interface SupportApiService {

    /**
     * ✅ FIXED: Khởi tạo conversation với customer_id
     */
    @POST("support-simple.php?action=init")
    @FormUrlEncoded
    Call<ConversationResponse> initConversation(
            @Field("customer_id") int customerId
    );

    /**
     * ✅ FIXED: Gửi tin nhắn TEXT với customer_id trong body
     */
    @POST("support-simple.php?action=send")
    Call<MessageResponse> sendMessage(@Body SendMessageRequest request);

    /**
     * ✅ FIXED: Gửi tin nhắn có FILE với customer_id
     */
    @Multipart
    @POST("support-simple.php?action=send")
    Call<MessageResponse> sendMessageWithAttachment(
            @Part("customer_id") RequestBody customerId,
            @Part("conversation_id") RequestBody conversationId,
            @Part("message") RequestBody message,
            @Part MultipartBody.Part attachment
    );

    /**
     * ✅ FIXED: Lấy lịch sử tin nhắn với customer_id
     */
    @GET("support-simple.php?action=history")
    Call<MessagesListResponse> getMessagesByConversation(
            @Query("customer_id") int customerId,
            @Query("conversation_id") String conversationId
    );

    /**
     * ✅ FIXED: Đếm số tin nhắn chưa đọc với customer_id
     */
    @GET("support-simple.php?action=unread-count")
    Call<UnreadCountResponse> getUnreadCount(
            @Query("customer_id") int customerId
    );

    /**
     * Lấy danh sách nhân viên support (không cần customer_id)
     */
    @GET("support-simple.php?action=employees")
    Call<EmployeesListResponse> getAvailableEmployees();

    /**
     * ✅ FIXED: Chuyển conversation với customer_id trong body
     */
    @POST("support-simple.php?action=transfer")
    Call<TransferResponse> transferConversation(@Body TransferRequest request);
}