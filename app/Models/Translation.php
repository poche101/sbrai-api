<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Translation extends Model
{
    protected $fillable = ['locale', 'group', 'key', 'value'];

    /**
     * Supported locales — must match Flutter's LanguageProvider codes.
     */
    public static array $supportedLocales = ['en', 'fr', 'ha', 'ig', 'yo'];

    /**
     * Human-readable locale labels for the Flutter language picker.
     */
    public static array $localeLabels = [
        'en' => 'English',
        'fr' => 'French',
        'ha' => 'Hausa',
        'ig' => 'Igbo',
        'yo' => 'Yoruba',
    ];

    /**
     * Translation groups (match Flutter ARB file convention).
     */
    public static array $groups = [
        'common', 'home', 'auth', 'ads',
        'categories', 'chat', 'dashboard',
        'profile', 'settings', 'voucher',
    ];
}
