<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            
            // ĐÃ SỬA: Dùng integer thay vì unsignedInteger để khớp với employees.employeeID (int)
            $table->integer('sender_id'); 
            $table->integer('receiver_id'); 
            
            $table->text('message');
            $table->timestamps();

            // Thiết lập khóa ngoại
            $table->foreign('sender_id')->references('employeeID')->on('employees')->onDelete('cascade');
            $table->foreign('receiver_id')->references('employeeID')->on('employees')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('messages');
    }
};