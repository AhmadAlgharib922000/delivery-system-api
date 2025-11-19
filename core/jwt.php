<?php
// api/core/jwt.php

// مفتاح سري للتوقيع (غيّره لقيمة قوية وخاصّة بمشروعك)
const JWT_SECRET = 'JAMELY_DELIVERY_SYSTEM_SUPER_SECRET_KEY_2025_x9F!@#';

/**
 * Base64Url encode
 */
function base64url_encode(string $data): string
{
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

/**
 * Base64Url decode
 */
function base64url_decode(string $data): string
{
    return base64_decode(strtr($data, '-_', '+/'));
}

/**
 * إنشاء توكين JWT
 */
function jwt_encode(array $payload, string $secret = JWT_SECRET): string
{
    $header = ['alg' => 'HS256', 'typ' => 'JWT'];

    $segments   = [];
    $segments[] = base64url_encode(json_encode($header));
    $segments[] = base64url_encode(json_encode($payload));

    $signingInput = implode('.', $segments);
    $signature    = hash_hmac('sha256', $signingInput, $secret, true);
    $segments[]   = base64url_encode($signature);

    return implode('.', $segments);
}

/**
 * قراءة توكين JWT
 */
function jwt_decode(string $token, string $secret = JWT_SECRET): ?array
{
    $parts = explode('.', $token);
    if (count($parts) !== 3) {
        return null;
    }

    [$headb64, $bodyb64, $sigb64] = $parts;

    $header    = json_decode(base64url_decode($headb64), true);
    $payload   = json_decode(base64url_decode($bodyb64), true);
    $signature = base64url_decode($sigb64);

    if (!$header || !$payload || !$signature) {
        return null;
    }

    if (($header['alg'] ?? '') !== 'HS256') {
        return null;
    }

    $signingInput = $headb64 . '.' . $bodyb64;
    $expected     = hash_hmac('sha256', $signingInput, $secret, true);

    if (!hash_equals($expected, $signature)) {
        return null;
    }

    // تحقق من وقت الانتهاء
    if (isset($payload['exp']) && time() > $payload['exp']) {
        return null;
    }

    return $payload;
}
