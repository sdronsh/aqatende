<?php

return [
    'policy' => [
        // Default cancel window for patients (hours before appointment).
        'patient_cancel_hours' => 4,
        // Default reschedule window for patients (hours before appointment).
        'patient_reschedule_hours' => 4,
        // Allow a professional to edit appointments from other professionals.
        'allow_professional_cross_edit' => true,
    ],
    'booking' => [
        'timezone' => env('BOOKING_TIMEZONE', 'America/Sao_Paulo'),
    ],
    'license' => [
        'api_url' => env('LICENSES_API_URL'),
        'api_token' => env('LICENSES_API_TOKEN'),
        'endpoint' => env('LICENSES_API_ENDPOINT', '/api/licenses/lookup'),
        'companies_endpoint' => env('LICENSES_COMPANIES_ENDPOINT', '/api/companies'),
        'subscriptions_endpoint' => env('LICENSES_SUBSCRIPTIONS_ENDPOINT', '/api/subscriptions'),
        'payment_url_template' => env('LICENSES_PAYMENT_URL_TEMPLATE'),
        'system_id' => env('APP_ID'),
        'cache_ttl' => 600,
        'stale_ttl' => 1800,
        'defaults' => [
            'user_limit' => 12,
            'clinic_limit' => 1,
            'unit_limit' => 2,
        ],
    ],
    'communication' => [
        'api_url' => env('COMMUNICATION_API_URL'),
        'api_token' => env('COMMUNICATION_API_TOKEN'),
        'webhook_token' => env('COMMUNICATION_WEBHOOK_TOKEN'),
    ],
    'subscription' => [
        'notification_email' => env('SUBSCRIPTION_NOTIFICATION_EMAIL', 'suporte@aqatende.com.br'),
    ],
];
