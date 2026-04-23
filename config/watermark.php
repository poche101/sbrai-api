<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Watermark Text
    |--------------------------------------------------------------------------
    | The text stamped across every uploaded image.
    | Override in .env:  WATERMARK_TEXT="© YourBrand"
    */
    'text' => env('WATERMARK_TEXT', '© SBRAI Solutions'),

    /*
    |--------------------------------------------------------------------------
    | Watermark Opacity  (0 = invisible, 100 = fully opaque)
    |--------------------------------------------------------------------------
    | 35–50 is typically ideal: visible enough to deter theft but not so
    | heavy that it ruins the listing photo.
    */
    'opacity' => (int) env('WATERMARK_OPACITY', 40),

];
