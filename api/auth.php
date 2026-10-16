<?php
/**
 * API Endpoint: /api/auth.php
 * Handles login, register, logout as JSON actions.
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/classes/Auth.php';

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed.']);
    exit;
}

$input  = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';
$auth   = new Auth();

try {
    switch ($action) {
        case 'register':
            $result = $auth->register(
                $input['username'] ?? '',
                $input['email']    ?? '',
                $input['password'] ?? ''
            );
            echo json_encode($result);
            break;

        case 'login':
            $result = $auth->login(
                $input['email']    ?? '',
                $input['password'] ?? ''
            );
            if ($result['success']) {
                $result['csrf_token'] = $_SESSION['csrf_token'];
                $result['username']   = $_SESSION['username'];
                $result['redirect']   = APP_URL . '/dashboard.php';
            }
            echo json_encode($result);
            break;

        case 'logout':
            $auth->logout();
            echo json_encode(['success' => true, 'redirect' => APP_URL . '/index.php']);
            break;

        case 'status':
            echo json_encode([
                'success'    => true,
                'logged_in'  => $auth->isLoggedIn(),
                'username'   => $_SESSION['username'] ?? null,
                'csrf_token' => $_SESSION['csrf_token'] ?? null,
            ]);
            break;

        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Unknown action.']);
    }
} catch (Throwable $e) {
    error_log('auth.php exception: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'An internal server error occurred.']);
}
