<?php
/**
 * Auth Class – Handles user registration, login, logout, and session guards.
 * Uses PDO prepared statements throughout to prevent SQL injection.
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/config.php';

class Auth
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->startSecureSession();
    }

    // ─── Session Bootstrap ────────────────────────────────────────────────────

    private function startSecureSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_set_cookie_params([
                'lifetime' => SESSION_LIFETIME,
                'path'     => '/',
                'secure'   => false,  // Set TRUE when running HTTPS
                'httponly' => true,
                'samesite' => 'Strict',
            ]);
            session_start();
        }
    }

    // ─── Registration ─────────────────────────────────────────────────────────

    /**
     * Register a new user.
     * @param  string $username
     * @param  string $email
     * @param  string $password  Plain-text; will be hashed with bcrypt
     * @return array  ['success' => bool, 'message' => string, 'user_id' => int|null]
     */
    public function register(string $username, string $email, string $password): array
    {
        // Sanitize inputs
        $username = trim(htmlspecialchars($username, ENT_QUOTES, 'UTF-8'));
        $email    = filter_var(trim($email), FILTER_SANITIZE_EMAIL);

        // Validate
        if (strlen($username) < 3 || strlen($username) > 50) {
            return $this->error('Username must be between 3 and 50 characters.');
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->error('Please provide a valid email address.');
        }
        if (!$this->isStrongPassword($password)) {
            return $this->error('Password must be at least 8 characters and include uppercase, lowercase, a number, and a special character.');
        }

        // Check uniqueness
        $existing = $this->db->fetchOne(
            'SELECT id FROM users WHERE email = :email OR username = :username',
            [':email' => $email, ':username' => $username]
        );
        if ($existing) {
            return $this->error('Email or username is already registered.');
        }

        // Hash and store
        $hash = password_hash($password, PASSWORD_ALGO, ['cost' => PASSWORD_COST]);
        $this->db->query(
            'INSERT INTO users (username, email, password) VALUES (:username, :email, :password)',
            [':username' => $username, ':email' => $email, ':password' => $hash]
        );

        return ['success' => true, 'message' => 'Account created successfully.', 'user_id' => (int)$this->db->lastInsertId()];
    }

    // ─── Login ────────────────────────────────────────────────────────────────

    /**
     * Authenticate a user and create a session.
     */
    public function login(string $email, string $password): array
    {
        $email = filter_var(trim($email), FILTER_SANITIZE_EMAIL);

        $user = $this->db->fetchOne(
            'SELECT id, username, email, password, role, is_active FROM users WHERE email = :email',
            [':email' => $email]
        );

        if (!$user || !password_verify($password, $user['password'])) {
            return $this->error('Invalid email or password.');
        }

        if (!$user['is_active']) {
            return $this->error('Your account has been deactivated. Please contact support.');
        }

        // Regenerate session ID to prevent session fixation
        session_regenerate_id(true);

        $_SESSION['user_id']    = $user['id'];
        $_SESSION['username']   = $user['username'];
        $_SESSION['user_role']  = $user['role'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['logged_in']  = true;
        $_SESSION['csrf_token'] = $this->generateCsrfToken();

        // Re-hash if needed (future-proofing)
        if (password_needs_rehash($user['password'], PASSWORD_ALGO, ['cost' => PASSWORD_COST])) {
            $newHash = password_hash($password, PASSWORD_ALGO, ['cost' => PASSWORD_COST]);
            $this->db->query(
                'UPDATE users SET password = :hash WHERE id = :id',
                [':hash' => $newHash, ':id' => $user['id']]
            );
        }

        return ['success' => true, 'message' => 'Logged in successfully.', 'role' => $user['role']];
    }

    // ─── Logout ───────────────────────────────────────────────────────────────

    public function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();
    }

    // ─── Guards ───────────────────────────────────────────────────────────────

    /** Returns true if a user is logged in. */
    public function isLoggedIn(): bool
    {
        return !empty($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    }

    /** Redirects to login page if the user is not authenticated. */
    public function requireAuth(): void
    {
        if (!$this->isLoggedIn()) {
            header('Location: ' . APP_URL . '/login.php');
            exit;
        }
    }

    /** Requires the logged-in user to have the 'admin' role. */
    public function requireAdmin(): void
    {
        $this->requireAuth();
        if (($_SESSION['user_role'] ?? '') !== 'admin') {
            http_response_code(403);
            exit('Access denied.');
        }
    }

    /** Returns the current logged-in user's ID or null. */
    public function userId(): ?int
    {
        return isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
    }

    // ─── CSRF ─────────────────────────────────────────────────────────────────

    public function generateCsrfToken(): string
    {
        $token = bin2hex(random_bytes(CSRF_TOKEN_LENGTH));
        $_SESSION['csrf_token'] = $token;
        return $token;
    }

    public function verifyCsrfToken(string $token): bool
    {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private function isStrongPassword(string $pass): bool
    {
        return strlen($pass) >= 8
            && preg_match('/[A-Z]/', $pass)
            && preg_match('/[a-z]/', $pass)
            && preg_match('/[0-9]/', $pass)
            && preg_match('/[\W_]/', $pass);
    }

    private function error(string $message): array
    {
        return ['success' => false, 'message' => $message, 'user_id' => null];
    }
}
