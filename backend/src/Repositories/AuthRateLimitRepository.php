<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class AuthRateLimitRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function isAllowed(string $rateKey, int $windowSeconds, int $maxAttempts): bool
    {
        $bucketStart = intdiv(time(), $windowSeconds) * $windowSeconds;

        $statement = $this->pdo->prepare(
            <<<'SQL'
            INSERT INTO auth_rate_limits (rate_key, bucket_start, attempts, first_seen_at, last_seen_at)
            VALUES (:rate_key, to_timestamp(:bucket_start), 1, now(), now())
            ON CONFLICT (rate_key, bucket_start) DO UPDATE
            SET attempts = auth_rate_limits.attempts + 1,
                last_seen_at = now()
            RETURNING attempts
            SQL
        );

        $statement->execute([
            'rate_key' => $rateKey,
            'bucket_start' => $bucketStart,
        ]);

        $attempts = (int) $statement->fetchColumn();
        return $attempts <= $maxAttempts;
    }
}

