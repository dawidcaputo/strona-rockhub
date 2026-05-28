<?php
// ============================================================
//  login.php – logowanie użytkownika
//  Odbiera JSON: { "email": "...", "haslo": "..." }
//  Zwraca  JSON: { "success": true/false, "message": "...", "user": {...} }
// ============================================================

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Tylko metoda POST jest dozwolona.']);
    exit;
}

require_once 'BazaDanych.php';

session_start();

// ── odczyt danych ──
$body  = json_decode(file_get_contents('php://input'), true);
$email = trim($body['email'] ?? '');
$haslo = trim($body['haslo'] ?? '');

if ($email === '' || $haslo === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Podaj e-mail i hasło.']);
    exit;
}

try {
    $pdo  = getConnection();
    $stmt = $pdo->prepare('SELECT id, imie, email, haslo_hash FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($haslo, $user['haslo_hash'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Nieprawidłowy e-mail lub hasło.']);
        exit;
    }

    // zapis sesji
    $_SESSION['user_id']    = $user['id'];
    $_SESSION['user_imie']  = $user['imie'];
    $_SESSION['user_email'] = $user['email'];

    echo json_encode([
        'success' => true,
        'message' => 'Zalogowano pomyślnie!',
        'user'    => [
            'id'    => $user['id'],
            'imie'  => $user['imie'],
            'email' => $user['email'],
        ],
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Błąd serwera: ' . $e->getMessage()]);
}
