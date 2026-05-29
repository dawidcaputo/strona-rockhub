<?php
// ============================================================
//  logout.php – wylogowanie użytkownika
// ============================================================

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

session_start();
session_unset();
session_destroy();

echo json_encode(['success' => true, 'message' => 'Wylogowano pomyślnie.']);