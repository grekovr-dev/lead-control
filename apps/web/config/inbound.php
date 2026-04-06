<?php

return [
    'capture' => [
        'visit_session_lifetime' => 'PT30M',
        'visitor_cookie_lifetime_days' => 30,
        'cookie_secure' => env('SESSION_SECURE_COOKIE', true),
    ],
];
