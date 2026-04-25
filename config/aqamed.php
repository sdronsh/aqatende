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
    'license' => [
        'api_url' => env('LICENSES_API_URL'),
        'api_token' => env('LICENSES_API_TOKEN'),
        'endpoint' => env('LICENSES_API_ENDPOINT', '/api/licenses/lookup'),
        'cache_ttl' => 600,
        'stale_ttl' => 1800,
        'defaults' => [
            'user_limit' => 12,
            'clinic_limit' => 1,
            'unit_limit' => 2,
        ],
    ],
];
