<?php
// ============================================================
//  register.php – rejestracja użytkownika
//  Odbiera JSON: { "imie": "...", "email": "...", "haslo": "..." }
//  Zwraca  JSON: { "success": true/false, "message": "..." }
// ============================================================

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');           // podczas testów lokalnych
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

// ── odczyt danych ──
$body = json_decode(file_get_contents('php://input'), true);

$imie  = trim($body['imie']  ?? '');
$email = trim($body['email'] ?? '');
$haslo = trim($body['haslo'] ?? '');

// ── walidacja ──
if ($imie === '' || $email === '' || $haslo === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Wszystkie pola są wymagane.']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Nieprawidłowy adres e-mail.']);
    exit;
}

if (strlen($haslo) < 6) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Hasło musi mieć minimum 6 znaków.']);
    exit;
}

try {
    $pdo = getConnection();

    // sprawdź czy email już istnieje
    $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);

    if ($stmt->fetch()) {
        http_response_code(409);
        echo json_encode(['success' => false, 'message' => 'Ten e-mail jest już zajęty.']);
        exit;
    }

    // hash hasła + zapis
    $haslo_hash = password_hash($haslo, PASSWORD_BCRYPT);

    $insert = $pdo->prepare(
        'INSERT INTO users (imie, email, haslo_hash) VALUES (?, ?, ?)'
    );
    $insert->execute([$imie, $email, $haslo_hash]);

    $newId = $pdo->lastInsertId();

    echo json_encode([
        'success' => true,
        'message' => 'Rejestracja zakończona sukcesem!',
        'user'    => [
            'id'    => $newId,
            'imie'  => $imie,
            'email' => $email,
        ],
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Błąd serwera: ' . $e->getMessage()]);
}
