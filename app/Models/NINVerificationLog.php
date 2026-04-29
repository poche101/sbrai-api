<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NINVerificationLog extends Model
{
    use HasFactory;

    protected $table = 'nin_verification_logs';

    protected $fillable = [
        'nin',
        'status',
        'request_data',
        'response_data',
        'verification_status',
        'tracking_id',
        'central_id',
        'firstname',
        'middlename',
        'surname',
        'photo',
        'signature',
        'birthdate',
        'gender',
        'phone_number',
        'email',
        'profession',
        'residence_address',
    ];

    protected $casts = [
        'request_data' => 'array',
        'response_data' => 'array',
    ];
}