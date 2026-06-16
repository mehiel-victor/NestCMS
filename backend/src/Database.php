<?php

declare(strict_types=1);

namespace App;

use PDO;

final class Database
{
    public static function connect(): PDO
    {
        $host = self::env('DB_HOST', 'postgres');
        $port = self::env('DB_PORT', '5432');
        $database = self::env('DB_NAME', 'nestcms');
        $user = self::env('DB_USER', 'nestcms');
        $password = self::env('DB_PASSWORD', 'nestcms');

        $pdo = new PDO(
            "pgsql:host={$host};port={$port};dbname={$database}",
            $user,
            $password,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]
        );

        $pdo->exec("SET TIME ZONE 'UTC'");

        return $pdo;
    }

    private static function env(string $key, string $default): string
    {
        $value = $_ENV[$key] ?? getenv($key);

        return $value === false || $value === null || $value === '' ? $default : (string) $value;
    }
}

