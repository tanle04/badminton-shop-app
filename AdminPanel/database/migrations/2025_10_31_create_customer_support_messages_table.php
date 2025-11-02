<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // ====================================================================
        // Bảng customer_support_messages
        // ====================================================================
        Schema::create('customer_support_messages', function (Blueprint $table) {
            $table->id();
            
            // ID của cuộc hội thoại (để nhóm tin nhắn)
            $table->string('conversation_id')->index();
            
            // Người gửi: customer hoặc employee (KHÔNG DÙNG UNSIGNED CHO SENDER_ID)
            $table->enum('sender_type', ['customer', 'employee']);
            
            // SỬA LỖI 1: Dùng integer thay vì unsignedBigInteger để khớp với INT(11) của employees/customers
            $table->integer('sender_id'); 
            
            // Nội dung tin nhắn
            $table->text('message');
            
            // File đính kèm (nếu có)
            $table->string('attachment_path')->nullable();
            $table->string('attachment_name')->nullable();
            
            // Trạng thái đã đọc
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            
            // Nhân viên được assign (nếu có)
            // SỬA LỖI 2: Dùng integer thay vì unsignedInteger để khớp với INT(11) của employeeID
            $table->integer('assigned_employee_id')->nullable(); 
            
            // Trạng thái cuộc hội thoại
            $table->enum('status', ['open', 'pending', 'resolved', 'closed'])->default('open');
            
            $table->timestamps();
            
            // Foreign keys
            // LỖI NẰM Ở KIỂU DỮ LIỆU CỘT (ĐÃ KHẮC PHỤC Ở TRÊN)
            $table->foreign('assigned_employee_id')
                    ->references('employeeID')
                    ->on('employees')
                    ->onDelete('set null');
            
            // Indexes
            $table->index(['sender_type', 'sender_id']);
            $table->index('created_at');
            $table->index('status');
        });
        
        // ====================================================================
        // Bảng theo dõi cuộc hội thoại
        // ====================================================================
        Schema::create('support_conversations', function (Blueprint $table) {
            $table->string('conversation_id')->primary();
            
            // SỬA LỖI 3: Dùng integer thay vì unsignedInteger để khớp với INT(11) của customerID
            $table->integer('customer_id'); 
            
            // SỬA LỖI 4: Dùng integer thay vì unsignedInteger để khớp với INT(11) của employeeID
            $table->integer('assigned_employee_id')->nullable(); 
            
            $table->enum('status', ['open', 'pending', 'resolved', 'closed'])->default('open');
            $table->enum('priority', ['low', 'normal', 'high', 'urgent'])->default('normal');
            $table->string('subject')->nullable();
            $table->timestamp('last_message_at')->nullable();
            $table->timestamps();
            
            $table->foreign('customer_id')
                    ->references('customerID')
                    ->on('customers')
                    ->onDelete('cascade');
                    
            $table->foreign('assigned_employee_id')
                    ->references('employeeID')
                    ->on('employees')
                    ->onDelete('set null');
                    
            $table->index('status');
            $table->index('assigned_employee_id');
            $table->index('last_message_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('support_conversations');
        Schema::dropIfExists('customer_support_messages');
    }
};