<?php

return [
    'url' => env('FACIAL_SERVICE_URL', 'http://facial-api:8001'),
    'token' => env('FACIAL_SERVICE_TOKEN'),
    'timeout' => (float) env('FACIAL_SERVICE_TIMEOUT', 5),
];
