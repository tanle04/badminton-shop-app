<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
// Lưu ý: Customer Model này không cần extends Authenticatable trừ khi bạn muốn cho phép đăng nhập

class Customer extends Model
{
    use HasFactory;
    
    protected $primaryKey = 'customerID';
    // Bảng có cột createdDate nhưng không có updated_at, nên tắt timestamps
    public $timestamps = false; 
    protected $guarded = [];
    
    // Đổi tên cột mật khẩu Laravel mặc định sang tên cột trong DB
    const CREATED_AT = 'createdDate';

    public function addresses()
    {
        return $this->hasMany(CustomerAddress::class, 'customerID', 'customerID');
    }
}