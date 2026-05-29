<?php
// ============================================================
//  session_check.php – sprawdź czy użytkownik jest zalogowany
// ============================================================

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

session_start();

if (!empty($_SESSION['user_id'])) {
    echo json_encode([
        'logged_in' => true,
        'user' => [
            'id'    => $_SESSION['user_id'],
            'imie'  => $_SESSION['user_imie'],
            'email' => $_SESSION['user_email'],
        ],
    ]);
} else {
    echo json_encode(['logged_in' => false]);
}
