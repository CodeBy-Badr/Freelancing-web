<?php
/**
 * API Endpoint: /api/review.php
 * Accepts POST with { code, language, csrf_token }
 * Returns JSON with { success, feedback } or { success, error }
 */

declare(strict_types=1);

// ─── Bootstrap ────────────────────────────────────────────────────────────────
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/classes/Auth.php';
require_once __DIR__ . '/../src/classes/AIService.php';
require_once __DIR__ . '/../src/classes/RateLimiter.php';

// ─── Security Headers ─────────────────────────────────────────────────────────
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Cache-Control: no-store, no-cache, must-revalidate');

// ─── Only accept POST ─────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed.']);
    exit;
}

// ─── Auth Guard ───────────────────────────────────────────────────────────────
$auth = new Auth();
if (!$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthenticated. Please log in.']);
    exit;
}

// ─── Parse JSON Body ──────────────────────────────────────────────────────────
$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid request body.']);
    exit;
}

// ─── CSRF Verification ────────────────────────────────────────────────────────
$csrfToken = $input['csrf_token'] ?? '';
if (!$auth->verifyCsrfToken($csrfToken)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'CSRF token mismatch.']);
    exit;
}

// ─── Input Validation & Sanitization ─────────────────────────────────────────
$allowedLanguages = ['html', 'css', 'javascript', 'typescript', 'php'];
$language = in_array(strtolower($input['language'] ?? ''), $allowedLanguages, true)
    ? strtolower($input['language'])
    : 'javascript';

$code = $input['code'] ?? '';
if (empty(trim($code))) {
    echo json_encode(['success' => false, 'error' => 'Code input cannot be empty.']);
    exit;
}
if (strlen($code) > 50000) {
    echo json_encode(['success' => false, 'error' => 'Code exceeds maximum length of 50,000 characters.']);
    exit;
}

// Strip any <script> or malicious HTML injection attempts in the code field
// Note: we store the original code as-is for review but never render it raw
$sanitizedCode = strip_tags($code);

// ─── Rate Limiting ────────────────────────────────────────────────────────────
$userId      = $auth->userId();
$rateLimiter = new RateLimiter();
if (!$rateLimiter->isAllowed($userId, 'review')) {
    $wait = $rateLimiter->secondsUntilReset($userId, 'review');
    http_response_code(429);
    echo json_encode([
        'success' => false,
        'error'   => "Rate limit exceeded. Try again in {$wait} seconds.",
    ]);
    exit;
}

// ─── AI Review ────────────────────────────────────────────────────────────────
try {
    $ai       = new AIService();
    $feedback = $ai->reviewCode($sanitizedCode, $language);

    // ─── Persist to DB ────────────────────────────────────────────────────────
    $db = Database::getInstance();
    $db->query(
        'INSERT INTO code_reviews (user_id, language, original_code, ai_feedback)
         VALUES (:uid, :lang, :code, :feedback)',
        [
            ':uid'      => $userId,
            ':lang'     => $language,
            ':code'     => $sanitizedCode,
            ':feedback' => $feedback,
        ]
    );

    echo json_encode([
        'success'  => true,
        'feedback' => $feedback,
        'review_id'=> $db->lastInsertId(),
    ]);

} catch (RuntimeException $e) {
    http_response_code(502);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
} catch (Throwable $e) {
    error_log('review.php exception: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'An internal error occurred.']);
}
