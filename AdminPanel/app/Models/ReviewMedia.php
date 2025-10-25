<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReviewMedia extends Model
{
    use HasFactory;

    protected $table = 'review_media';
    protected $primaryKey = 'mediaID';
    public $timestamps = false; // Không có created_at, updated_at
    
    protected $fillable = [
        'reviewID', 
        'mediaUrl', 
        'mediaType'
    ];

    /**
     * Mối quan hệ: Media thuộc về một Đánh giá.
     */
    public function review()
    {
        return $this->belongsTo(Review::class, 'reviewID', 'reviewID');
    }
}