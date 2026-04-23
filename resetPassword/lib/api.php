<?php

function api_patch_json(string $url, array $payload, int $timeoutSeconds = 10): array
{
    $jsonPayload = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

    if ($jsonPayload === false) {
        return [
            'ok' => false,
            'status' => 0,
            'data' => null,
            'error' => 'No se pudo serializar el cuerpo JSON.',
        ];
    }

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
            CURLOPT_CONNECTTIMEOUT => min(5, $timeoutSeconds),
        ]);

        $rawBody = curl_exec($ch);
        $curlError = curl_error($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($rawBody === false) {
            return [
                'ok' => false,
                'status' => 0,
                'data' => null,
                'error' => $curlError ?: 'No se pudo conectar con la API.',
            ];
        }

        return normalize_api_response($status, $rawBody);
    }

    $context = stream_context_create([
        'http' => [
            'method' => 'PATCH',
            'header' => implode("\r\n", [
                'Content-Type: application/json',
                'Accept: application/json',
                'Content-Length: ' . strlen($jsonPayload),
            ]),
            'content' => $jsonPayload,
            'timeout' => $timeoutSeconds,
            'ignore_errors' => true,
        ],
    ]);

    $rawBody = @file_get_contents($url, false, $context);

    if ($rawBody === false) {
        return [
            'ok' => false,
            'status' => 0,
            'data' => null,
            'error' => 'No se pudo conectar con la API.',
        ];
    }

    $status = 0;
    if (!empty($http_response_header)) {
        foreach ($http_response_header as $headerLine) {
            if (preg_match('/HTTP\/\S+\s+(\d{3})/', $headerLine, $matches)) {
                $status = (int) $matches[1];
                break;
            }
        }
    }

    return normalize_api_response($status, $rawBody);
}

function normalize_api_response(int $status, string $rawBody): array
{
    $decoded = json_decode($rawBody, true);

    if (!is_array($decoded)) {
        $decoded = null;
    }

    return [
        'ok' => $status >= 200 && $status < 300,
        'status' => $status,
        'data' => $decoded,
        'raw' => $rawBody,
        'error' => null,
    ];
}
