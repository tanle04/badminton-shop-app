<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Slider extends Model
{
    use HasFactory;

    protected $table = 'sliders';
    protected $primaryKey = 'sliderID';
    // Sử dụng createdDate làm CREATED_AT
    const CREATED_AT = 'createdDate'; 
    const UPDATED_AT = null; // Không dùng updated_at
    
    protected $fillable = [
        'title', 
        'imageUrl', 
        'backlink', 
        'employeeID', 
        'status', 
        'notes'
    ];

    // Mối quan hệ: Slider được tạo bởi một Employee
    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employeeID', 'employeeID');
    }
}