<?php
/**
 * RateLimiter – Prevents API abuse using DB-backed request windows.
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/config.php';

class RateLimiter
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Check if the user has exceeded their rate limit for an endpoint.
     *
     * @param  int    $userId   Authenticated user ID
     * @param  string $endpoint e.g. 'review' | 'chat'
     * @return bool   TRUE if the request is allowed, FALSE if rate-limited
     */
    public function isAllowed(int $userId, string $endpoint): bool
    {
        $row = $this->db->fetchOne(
            'SELECT id, requests, window_start FROM rate_limits WHERE user_id = :uid AND endpoint = :ep',
            [':uid' => $userId, ':ep' => $endpoint]
        );

        $now = new DateTimeImmutable();

        if (!$row) {
            // First request – create window
            $this->db->query(
                'INSERT INTO rate_limits (user_id, endpoint, requests, window_start)
                 VALUES (:uid, :ep, 1, :now)',
                [':uid' => $userId, ':ep' => $endpoint, ':now' => $now->format('Y-m-d H:i:s')]
            );
            return true;
        }

        $windowStart = new DateTimeImmutable($row['window_start']);
        $elapsed     = $now->getTimestamp() - $windowStart->getTimestamp();

        if ($elapsed > RATE_LIMIT_WINDOW) {
            // Window expired – reset
            $this->db->query(
                'UPDATE rate_limits SET requests = 1, window_start = :now WHERE id = :id',
                [':now' => $now->format('Y-m-d H:i:s'), ':id' => $row['id']]
            );
            return true;
        }

        if ((int)$row['requests'] >= RATE_LIMIT_REQUESTS) {
            return false; // Rate limited
        }

        // Increment counter
        $this->db->query(
            'UPDATE rate_limits SET requests = requests + 1 WHERE id = :id',
            [':id' => $row['id']]
        );
        return true;
    }

    /**
     * Returns the number of seconds until the current window resets.
     */
    public function secondsUntilReset(int $userId, string $endpoint): int
    {
        $row = $this->db->fetchOne(
            'SELECT window_start FROM rate_limits WHERE user_id = :uid AND endpoint = :ep',
            [':uid' => $userId, ':ep' => $endpoint]
        );
        if (!$row) return 0;

        $elapsed = time() - strtotime($row['window_start']);
        return max(0, RATE_LIMIT_WINDOW - $elapsed);
    }
}
