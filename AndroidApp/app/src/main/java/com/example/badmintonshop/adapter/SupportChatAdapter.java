package com.example.badmintonshop.adapter;

import android.content.Context;
import android.view.Gravity;
import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.widget.LinearLayout;
import android.widget.TextView;

import androidx.annotation.NonNull;
import androidx.recyclerview.widget.RecyclerView;

import com.example.badmintonshop.R;
import com.example.badmintonshop.network.dto.SupportMessage;

import java.text.ParseException;
import java.text.SimpleDateFormat;
import java.util.Date;
import java.util.List;
import java.util.Locale;

/**
 * Adapter cho RecyclerView hiển thị tin nhắn chat
 */
public class SupportChatAdapter extends RecyclerView.Adapter<SupportChatAdapter.MessageViewHolder> {

    private Context context;
    private List<SupportMessage> messagesList;
    private int currentCustomerId;

    public SupportChatAdapter(Context context, List<SupportMessage> messagesList, int currentCustomerId) {
        this.context = context;
        this.messagesList = messagesList;
        this.currentCustomerId = currentCustomerId;
    }

    @NonNull
    @Override
    public MessageViewHolder onCreateViewHolder(@NonNull ViewGroup parent, int viewType) {
        View view = LayoutInflater.from(context).inflate(R.layout.item_chat_message, parent, false);
        return new MessageViewHolder(view);
    }

    @Override
    public void onBindViewHolder(@NonNull MessageViewHolder holder, int position) {
        SupportMessage message = messagesList.get(position);

        boolean isMyMessage = message.isFromCustomer();

        // Setup layout gravity
        LinearLayout.LayoutParams params = (LinearLayout.LayoutParams) holder.messageContainer.getLayoutParams();
        if (isMyMessage) {
            params.gravity = Gravity.END;
            holder.messageContainer.setBackgroundResource(R.drawable.bg_message_sent);
        } else {
            params.gravity = Gravity.START;
            holder.messageContainer.setBackgroundResource(R.drawable.bg_message_received);
        }
        holder.messageContainer.setLayoutParams(params);

        // Set message text
        holder.textViewMessage.setText(message.getMessage());

        // Set sender name (chỉ hiển thị cho tin nhắn từ employee)
        if (message.isFromEmployee() && message.getSender() != null) {
            holder.textViewSenderName.setVisibility(View.VISIBLE);
            holder.textViewSenderName.setText(message.getSender().getFullName());
        } else {
            holder.textViewSenderName.setVisibility(View.GONE);
        }

        // Set timestamp
        holder.textViewTime.setText(formatTime(message.getCreatedAt()));

        // Hiển thị attachment nếu có
        if (message.getAttachmentPath() != null && !message.getAttachmentPath().isEmpty()) {
            holder.textViewAttachment.setVisibility(View.VISIBLE);
            holder.textViewAttachment.setText("📎 " + message.getAttachmentName());

            // TODO: Add click listener để xem file
        } else {
            holder.textViewAttachment.setVisibility(View.GONE);
        }
    }

    @Override
    public int getItemCount() {
        return messagesList.size();
    }

    /**
     * Format timestamp
     */
    private String formatTime(String isoTime) {
        try {
            SimpleDateFormat inputFormat = new SimpleDateFormat("yyyy-MM-dd'T'HH:mm:ss", Locale.getDefault());
            SimpleDateFormat outputFormat = new SimpleDateFormat("HH:mm", Locale.getDefault());

            Date date = inputFormat.parse(isoTime);
            if (date != null) {
                return outputFormat.format(date);
            }
        } catch (ParseException e) {
            e.printStackTrace();
        }
        return "";
    }

    /**
     * ViewHolder
     */
    static class MessageViewHolder extends RecyclerView.ViewHolder {
        LinearLayout messageContainer;
        TextView textViewSenderName;
        TextView textViewMessage;
        TextView textViewTime;
        TextView textViewAttachment;

        public MessageViewHolder(@NonNull View itemView) {
            super(itemView);
            messageContainer = itemView.findViewById(R.id.messageContainer);
            textViewSenderName = itemView.findViewById(R.id.textViewSenderName);
            textViewMessage = itemView.findViewById(R.id.textViewMessage);
            textViewTime = itemView.findViewById(R.id.textViewTime);
            textViewAttachment = itemView.findViewById(R.id.textViewAttachment);
        }
    }
}