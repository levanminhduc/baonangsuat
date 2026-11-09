<?php
require_once __DIR__ . '/../authV2/bootstrap.php';

function generateCsrfToken() {
    if (empty($_SESSION['bns_csrf_token'])) {
        $_SESSION['bns_csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['bns_csrf_token'];
}

function getCsrfToken() {
    return generateCsrfToken();
}

function validateCsrfToken($token) {
    if (empty($_SESSION['bns_csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['bns_csrf_token'], $token);
}

function verifyCsrfToken() {
    $token = $_POST['csrf_token'] ?? $_GET['csrf_token'] ?? '';
    if (!validateCsrfToken($token)) {
        http_response_code(403);
        die('Invalid CSRF token');
    }
    return true;
}
?>
