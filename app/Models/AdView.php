<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdView extends Model
{
    protected $fillable = ['ad_id', 'viewer_id', 'ip_address'];

    public function ad()
    {
        return $this->belongsTo(Ad::class);
    }

    public function viewer()
    {
        return $this->belongsTo(User::class, 'viewer_id');
    }
}
