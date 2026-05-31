<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdFavorite extends Model
{
    use HasFactory;

    // Important: Tell Laravel the correct table name
    protected $table = 'ad_favorites';

    protected $fillable = ['ad_id', 'user_id'];

    /**
     * Get the ad that was favorited
     */
    public function ad()
    {
        return $this->belongsTo(Ad::class);
    }

    /**
     * Get the user who favorited the ad
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
