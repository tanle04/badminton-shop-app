<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    use HasFactory;

    protected $table = 'reviews';
    protected $primaryKey = 'reviewID';
    // Sử dụng reviewDate làm CREATED_AT
    const CREATED_AT = 'reviewDate'; 
    const UPDATED_AT = null;
    
    protected $fillable = [
        'orderDetailID', 
        'customerID', 
        'productID', 
        'rating', 
        'reviewContent', 
        'status'
    ];
    
    protected $casts = [
        'rating' => 'integer',
        'isReviewed' => 'boolean',
    ];

    /**
     * Mối quan hệ: Đánh giá thuộc về một Khách hàng.
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customerID', 'customerID');
    }

    /**
     * Mối quan hệ: Đánh giá dành cho một Sản phẩm.
     */
    public function product()
    {
        return $this->belongsTo(Product::class, 'productID', 'productID');
    }

    /**
     * Mối quan hệ: Đánh giá liên kết với một chi tiết đơn hàng (đảm bảo khách đã mua).
     */
    public function orderDetail()
    {
        return $this->belongsTo(OrderDetail::class, 'orderDetailID', 'orderDetailID');
    }
    
    /**
     * Mối quan hệ: Một đánh giá có thể có nhiều Ảnh/Video (Review Media).
     */
    public function media()
    {
        return $this->hasMany(ReviewMedia::class, 'reviewID', 'reviewID');
    }
}