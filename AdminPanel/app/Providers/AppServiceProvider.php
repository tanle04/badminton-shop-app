<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AppServiceProvider extends ServiceProvider
{
    public function register()
    {
        //
    }

    public function boot()
    {
        // FIX: Sửa lại Macro để sử dụng phương thức 'renameColumn' chính xác
        Blueprint::macro('renameColumn', function ($from, $to) {
            // Trong Laravel 9+, phương thức renameColumn() của Blueprint 
            // đã được thay thế. Bạn nên sử dụng Schema::renameColumn
            // nếu không muốn dùng macro. Nếu dùng macro, cú pháp phải là:
            // $this->change() or Schema::table

            // Cú pháp đúng để tạo macro hỗ trợ MariaDB/MySql cũ:
            // Thay thế: $this->rename($from, $to);
            // Bằng: $this->change();

            // Để đơn giản và tránh lỗi IDE/MariaDB, hãy bỏ macro và sử dụng 
            // phương thức có sẵn của Schema (nếu phiên bản Laravel của bạn hỗ trợ).

            // Nếu bạn muốn giữ lại Macro, hãy sửa thành:
            // (Tuy nhiên, macro này không được khuyến nghị vì nó không phải là giải pháp tốt nhất cho MariaDB)
            // $this->change(); 
            // Phương pháp tốt nhất là để code macro ban đầu của bạn là:
            $this->rename($from, $to);
        });

        // Cần xóa Macro này nếu nó gây xung đột
        // Tốt hơn hết là KHÔNG dùng macro này trừ khi bạn gặp lỗi khi chạy migration.

        // Giữ lại dòng này vì nó cần thiết cho MariaDB/MySQL phiên bản cũ:
        Schema::defaultStringLength(191);
    }
}
