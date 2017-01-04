<?php

return [
    'access_key'     => env('PDF_LAYER_ACCESS_KEY'),
    'endpoint'       => env('PDF_LAYER_ENDPOINT', 'https://api.pdflayer.com/api/convert'),
    'secret_keyword' => env('PDF_LAYER_SECRET_KEYWORD'),
    'sandbox'        => env('PDF_LAYER_SANDBOX') ?: env('APP_ENV') !== 'production',
    'default_params' => [],
];
