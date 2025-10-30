<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Slider extends Model
{
    protected $primaryKey = 'sliderID';
    
    protected $fillable = [
        'imageUrl',
        'title',
        'backlink',
        'notes',
        'status',
        'displayOrder',
        'employeeID'
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    // Timestamps
    public $timestamps = true;

    // ============ RELATIONSHIPS ============
    
    /**
     * Slider được tạo bởi employee
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employeeID', 'employeeID');
    }

    // ============ SCOPES ============
    
    /**
     * Scope: Lấy các slider đang hoạt động
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope: Sắp xếp theo thứ tự hiển thị
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('displayOrder', 'asc');
    }

    // ============ ACCESSORS ============
    
    /**
     * Lấy URL ảnh đầy đủ
     */
    public function getImageUrlFullAttribute()
    {
        if ($this->imageUrl) {
            return asset('storage/' . $this->imageUrl);
        }
        return asset('images/no-image.png');
    }

    /**
     * Kiểm tra slider có đang hoạt động không
     */
    public function getIsActiveAttribute()
    {
        return $this->status === 'active';
    }

    // ============ METHODS ============
    
    /**
     * Toggle trạng thái
     */
    public function toggleStatus()
    {
        $this->status = $this->status === 'active' ? 'inactive' : 'active';
        $this->save();
        
        return $this;
    }
}