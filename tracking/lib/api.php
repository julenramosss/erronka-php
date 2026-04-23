<?php

function api_patch_json(string $url, array $payload, int $timeoutSeconds = 10): array
{
    $jsonPayload = json_encode($payload);

    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST => 'PATCH',
            CURLOPT_POSTFIELDS => $jsonPayload,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Accept: application/json',
                'Content-Length: ' . strlen($jsonPayload),
            ],
            CURLOPT_TIMEOUT => $timeoutSeconds,
            CURLOPT_CONNECTTIMEOUT => 5,
        ]);

        $rawBody = curl_exec($ch);
        $curlError = curl_error($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($rawBody === false) {
            return [
                'status' => 0,
                'data' => null,
                'error' => $curlError ?: 'Fallo desconocido en CURL',
            ];
        }

        return [
            'status' => $status,
            'data' => json_decode($rawBody, true),
            'error' => null
        ];
    }

    return [
        'status' => 0,
        'data' => null,
        'error' => 'CURL no está instalado en este servidor.',
    ];
}

function api_get_json(string $url, int $timeoutSeconds = 10): array
{
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Accept: application/json',
            ],
            CURLOPT_TIMEOUT => $timeoutSeconds,
            CURLOPT_CONNECTTIMEOUT => 5,
        ]);

        $rawBody = curl_exec($ch);
        $curlError = curl_error($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($rawBody === false) {
            return [
                'status' => 0,
                'data' => null,
                'error' => $curlError ?: 'Fallo desconocido en CURL',
            ];
        }

        return [
            'status' => $status,
            'data' => json_decode($rawBody, true),
            'error' => null
        ];
    }

    return [
        'status' => 0,
        'data' => null,
        'error' => 'CURL no está instalado en este servidor.',
    ];
}