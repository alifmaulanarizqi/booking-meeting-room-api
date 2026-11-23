<?php

return [
    'secret' => env('JWT_SECRET', 'meeting-booking-secret-key-minimal-32-chars'),
    'ttl' => (int) env('JWT_TTL', 86400), // 24 jam
];