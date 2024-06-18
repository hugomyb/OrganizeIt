<?php

return [
    'email' => [
        'recipient' => ['hugomayonobe@gmail.com'],
        'bcc' => [],
        'cc' => [],
        'subject' => 'Une erreur est survenue - ' . env('APP_NAME'),
    ],

    'disabledOn' => [
        'local'
    ],

    'cacheCooldown' => 10, // in minutes
];
