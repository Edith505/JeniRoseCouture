<?php

declare(strict_types=1);

namespace App\Auth;

use RuntimeException;

final class AdminSession
{
    private const IDLE_SECONDS = 1800;
    private const LIFETIME_SECONDS = 28800;

    public static function start(): void
    {
        $secure = isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off';

        if (!session_start([
            'name' => 'jeni_admin',
            'use_strict_mode' => 1,
            'use_only_cookies' => 1,
            'cookie_httponly' => true,
            'cookie_secure' => $secure,
            'cookie_samesite' => 'Lax',
        ])) {
            throw new RuntimeException('Impossible de démarrer la session.');
        }

        if (isset($_SESSION['user_id']) && (
            time() - $_SESSION['last_seen'] >= self::IDLE_SECONDS
            || time() - $_SESSION['signed_in_at'] >= self::LIFETIME_SECONDS
        )) {
            self::clear();
        }
    }

    public static function token(): string
    {
        return $_SESSION['csrf'] ??= bin2hex(random_bytes(32));
    }

    public static function acceptsToken(mixed $token): bool
    {
        return is_string($token) && isset($_SESSION['csrf'])
            && hash_equals($_SESSION['csrf'], $token);
    }

    public static function signIn(int $userId): void
    {
        self::clear();
        $_SESSION['user_id'] = $userId;
        $_SESSION['signed_in_at'] = time();
        self::touch();
    }

    public static function userId(): ?int
    {
        return $_SESSION['user_id'] ?? null;
    }

    public static function touch(): void
    {
        $_SESSION['last_seen'] = time();
    }

    public static function clear(): void
    {
        $_SESSION = [];
        if (!session_regenerate_id(true)) {
            throw new RuntimeException('Impossible de renouveler la session.');
        }
    }
}
