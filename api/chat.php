<?php
/**
 * API Endpoint: /api/chat.php
 * Manages mock interview sessions with conversation history in PHP sessions.
 * Accepts POST with { message, role, action, csrf_token }
 * Actions: 'start' | 'message' | 'end'
 */

declare(strict_types=1);

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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed.']);
    exit;
}

// ─── Auth ─────────────────────────────────────────────────────────────────────
$auth = new Auth();
if (!$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthenticated.']);
    exit;
}

// ─── Parse Input ──────────────────────────────────────────────────────────────
$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid JSON body.']);
    exit;
}

// ─── CSRF ─────────────────────────────────────────────────────────────────────
if (!$auth->verifyCsrfToken($input['csrf_token'] ?? '')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'CSRF token mismatch.']);
    exit;
}

// ─── Rate Limiting ────────────────────────────────────────────────────────────
$userId      = $auth->userId();
$rateLimiter = new RateLimiter();
if (!$rateLimiter->isAllowed($userId, 'chat')) {
    $wait = $rateLimiter->secondsUntilReset($userId, 'chat');
    http_response_code(429);
    echo json_encode(['success' => false, 'error' => "Rate limit exceeded. Try again in {$wait}s."]);
    exit;
}

// ─── Allowed Roles ────────────────────────────────────────────────────────────
$allowedRoles = [
    'Frontend Developer',
    'Backend Engineer',
    'Full-Stack Developer',
    'PHP Developer',
    'React Developer',
];
$action  = $input['action'] ?? 'message';
$devRole = in_array($input['role'] ?? '', $allowedRoles, true)
    ? $input['role']
    : 'Full-Stack Developer';

try {
    $ai = new AIService();
    $db = Database::getInstance();

    // ── ACTION: start ──────────────────────────────────────────────────────────
    if ($action === 'start') {
        // Clear any previous interview session
        unset($_SESSION['interview_history'], $_SESSION['interview_role'], $_SESSION['interview_db_id']);

        $_SESSION['interview_role']    = $devRole;
        $_SESSION['interview_history'] = [];

        // Kick off interview – AI sends the opening question
        $aiResponse = $ai->conductInterview([], $devRole);

        $_SESSION['interview_history'][] = ['role' => 'assistant', 'content' => $aiResponse];

        // Create a new DB session record
        $db->query(
            'INSERT INTO interview_sessions (user_id, role, transcript_json, status)
             VALUES (:uid, :role, :json, "active")',
            [
                ':uid'  => $userId,
                ':role' => $devRole,
                ':json' => json_encode($_SESSION['interview_history']),
            ]
        );
        $_SESSION['interview_db_id'] = (int)$db->lastInsertId();

        echo json_encode(['success' => true, 'message' => $aiResponse, 'action' => 'start']);
        exit;
    }

    // ── ACTION: message ────────────────────────────────────────────────────────
    if ($action === 'message') {
        $userMessage = trim($input['message'] ?? '');
        if (empty($userMessage)) {
            echo json_encode(['success' => false, 'error' => 'Message cannot be empty.']);
            exit;
        }
        if (strlen($userMessage) > 5000) {
            echo json_encode(['success' => false, 'error' => 'Message too long (max 5000 chars).']);
            exit;
        }

        // Sanitize user message
        $userMessage = htmlspecialchars($userMessage, ENT_QUOTES, 'UTF-8');

        if (!isset($_SESSION['interview_history'])) {
            echo json_encode(['success' => false, 'error' => 'No active session. Please start a new interview.']);
            exit;
        }

        // Append user message to history
        $_SESSION['interview_history'][] = ['role' => 'user', 'content' => $userMessage];

        // Get AI reply
        $devRole    = $_SESSION['interview_role'] ?? 'Full-Stack Developer';
        $aiResponse = $ai->conductInterview($_SESSION['interview_history'], $devRole);

        $_SESSION['interview_history'][] = ['role' => 'assistant', 'content' => $aiResponse];

        // Update DB transcript
        if (isset($_SESSION['interview_db_id'])) {
            $db->query(
                'UPDATE interview_sessions SET transcript_json = :json WHERE id = :id',
                [
                    ':json' => json_encode($_SESSION['interview_history']),
                    ':id'   => $_SESSION['interview_db_id'],
                ]
            );
        }

        echo json_encode(['success' => true, 'message' => $aiResponse, 'action' => 'message']);
        exit;
    }

    // ── ACTION: end ────────────────────────────────────────────────────────────
    if ($action === 'end') {
        if (!isset($_SESSION['interview_db_id'])) {
            echo json_encode(['success' => false, 'error' => 'No active session to end.']);
            exit;
        }

        // Extract final score from the last AI message (regex for score pattern)
        $lastMsg   = end($_SESSION['interview_history'])['content'] ?? '';
        $score     = null;
        if (preg_match('/Overall[^:]*:\s*\*{0,2}(\d+(?:\.\d+)?)\s*\/\s*10/i', $lastMsg, $m)) {
            $score = min(10, max(0, (float)$m[1]));
        }

        $db->query(
            'UPDATE interview_sessions SET status = "completed", final_score = :score, transcript_json = :json WHERE id = :id',
            [
                ':score' => $score,
                ':json'  => json_encode($_SESSION['interview_history']),
                ':id'    => $_SESSION['interview_db_id'],
            ]
        );

        // Clean session interview data
        unset($_SESSION['interview_history'], $_SESSION['interview_role'], $_SESSION['interview_db_id']);

        echo json_encode(['success' => true, 'action' => 'end', 'final_score' => $score]);
        exit;
    }

    echo json_encode(['success' => false, 'error' => 'Unknown action.']);

} catch (RuntimeException $e) {
    http_response_code(502);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
} catch (Throwable $e) {
    error_log('chat.php exception: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'An internal server error occurred.']);
}
