<?php

namespace App\Middleware;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class JwtHelper
{
    private static $algorithm = 'HS256';

    private static function getSecretKey(): string
    {
        $secret = getenv('JWT_SECRET');

        if (!$secret || strlen($secret) < 32) {
            throw new \RuntimeException(
                'JWT_SECRET manquante ou trop courte'
            );
        }

        return $secret;
    }

    public static function generateToken($data, $expiry = 3600)
    {
        $issuedAt = time();

        $payload = [
            'iat' => $issuedAt,
            'exp' => $issuedAt + $expiry,
            'data' => $data
        ];

        return JWT::encode(
            $payload,
            self::getSecretKey(),
            self::$algorithm
        );
    }

    public static function validateToken($token)
    {
        try {
            return JWT::decode(
                $token,
                new Key(self::getSecretKey(), self::$algorithm)
            );
        } catch (\Exception $e) {
            return null;
        }
    }
}
