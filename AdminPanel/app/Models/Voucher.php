<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Voucher extends Model
{
    protected $primaryKey = 'voucherID';
    
    protected $fillable = [
        'voucherCode',
        'voucherName',
        'description',
        'discountType',
        'discountValue',
        'minOrderValue',
        'maxDiscountAmount',
        'maxUsage',
        'usedCount',
        'startDate',
        'endDate',
        'isActive',
        'isPrivate'
    ];

    protected $casts = [
        'isActive' => 'boolean',
        'isPrivate' => 'boolean',
        'startDate' => 'datetime',
        'endDate' => 'datetime',
        'discountValue' => 'decimal:2',
        'minOrderValue' => 'decimal:2',
        'maxDiscountAmount' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];
    
    // Timestamps
    public $timestamps = true;

    // ============ BOOT METHOD ============
    protected static function boot()
    {
        parent::boot();
        
        // Tự động set voucherName = voucherCode nếu chưa có
        static::creating(function ($voucher) {
            if (empty($voucher->voucherName)) {
                $voucher->voucherName = $voucher->voucherCode;
            }
        });
    }

    // ============ SCOPES ============
    
    /**
     * Scope: Lấy các voucher đang hoạt động
     */
    public function scopeActive($query)
    {
        return $query->where('isActive', true)
                    ->where('startDate', '<=', now())
                    ->where('endDate', '>=', now());
    }

    /**
     * Scope: Lấy các voucher công khai
     */
    public function scopePublic($query)
    {
        return $query->where('isPrivate', false);
    }

    // ============ ACCESSORS ============
    
    /**
     * Lấy trạng thái hiện tại của voucher
     */
    public function getStatusAttribute()
    {
        if (!$this->isActive) return 'inactive';
        if ($this->endDate < now()) return 'expired';
        if ($this->startDate > now()) return 'upcoming';
        return 'active';
    }

    /**
     * Kiểm tra voucher đã hết hạn chưa
     */
    public function getIsExpiredAttribute()
    {
        return $this->endDate < now();
    }

    /**
     * Kiểm tra voucher có thể sử dụng được không
     */
    public function getIsAvailableAttribute()
    {
        return $this->isActive 
            && $this->startDate <= now() 
            && $this->endDate >= now()
            && $this->usedCount < $this->maxUsage;
    }

    /**
     * Số lần sử dụng còn lại
     */
    public function getRemainingUsageAttribute()
    {
        return max(0, $this->maxUsage - $this->usedCount);
    }

    /**
     * Phần trăm đã sử dụng
     */
    public function getUsagePercentageAttribute()
    {
        return $this->maxUsage > 0 
            ? round(($this->usedCount / $this->maxUsage) * 100, 1) 
            : 0;
    }

    // ============ METHODS ============
    
    /**
     * Tính số tiền giảm giá cho đơn hàng
     */
    public function calculateDiscount($orderTotal)
    {
        if ($orderTotal < $this->minOrderValue) {
            return 0;
        }

        if ($this->discountType === 'percentage') {
            $discount = $orderTotal * ($this->discountValue / 100);
            if ($this->maxDiscountAmount) {
                $discount = min($discount, $this->maxDiscountAmount);
            }
        } else {
            $discount = $this->discountValue;
        }

        return min($discount, $orderTotal);
    }

    /**
     * Tăng số lần đã sử dụng
     */
    public function incrementUsage()
    {
        $this->increment('usedCount');
    }

    // ============ RELATIONSHIPS ============
    
    /**
     * Voucher được sử dụng trong các đơn hàng
     */
    public function orders()
    {
        return $this->hasMany(Order::class, 'voucherID', 'voucherID');
    }
}