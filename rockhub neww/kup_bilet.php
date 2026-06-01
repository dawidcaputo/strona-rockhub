<?php
// ============================================================
//  kup_bilet.php – zakup biletu + email potwierdzający
// ============================================================

error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json; charset=utf-8');

session_start();
require_once 'BazaDanych.php';

// ── tylko zalogowani ──
if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Musisz być zalogowany.']);
    exit;
}

$user_id    = $_SESSION['user_id'];
$user_email = $_SESSION['user_email'];
$user_imie  = $_SESSION['user_imie'];

// ── odczyt danych ──
$body = json_decode(file_get_contents('php://input'), true);

$event_id   = (int)($body['event_id']   ?? 0);
$event_name = trim($body['event_name']  ?? '');
$strefa     = trim($body['strefa']      ?? '');
$ilosc      = (int)($body['ilosc']      ?? 1);
$cena       = (float)($body['cena']     ?? 0);
$miasto     = trim($body['miasto']      ?? '');
$data       = trim($body['data']        ?? '');
$venue      = trim($body['venue']       ?? '');

if (!$event_id || !in_array($strefa, ['stojace','siedzace']) || $ilosc < 1 || $cena <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Nieprawidłowe dane zamówienia.']);
    exit;
}

$suma = $cena * $ilosc;

try {
    $pdo = getConnection();

    // zapis zamówienia w bazie
    $stmt = $pdo->prepare('
        INSERT INTO orders (user_id, event_id, strefa, ilosc, cena_jednostkowa, suma, status)
        VALUES (?, ?, ?, ?, ?, ?, "oplacone")
    ');
    $stmt->execute([$user_id, $event_id, $strefa, $ilosc, $cena, $suma]);
    $order_id = $pdo->lastInsertId();

    // ── wyślij email ──
    $strefaLabel = $strefa === 'stojace' ? 'Stojące' : 'Siedzące';
    $subject = "Rockhub – Twój bilet na " . $event_name;

    $message = "
    <html>
    <head><meta charset='utf-8'></head>
    <body style='font-family:Inter,Arial,sans-serif;background:#0a0a0a;color:#ffffff;padding:40px;'>
        <div style='max-width:560px;margin:0 auto;background:#141414;border-radius:20px;overflow:hidden;'>

            <div style='background:#e63946;padding:32px 40px;text-align:center;'>
                <h1 style='font-size:28px;letter-spacing:3px;margin:0;'>ROCKHUB</h1>
                <p style='color:rgba(255,255,255,0.8);margin:8px 0 0;font-size:13px;letter-spacing:1px;'>POTWIERDZENIE ZAKUPU</p>
            </div>

            <div style='padding:40px;'>
                <p style='font-size:16px;color:#aaa;margin-bottom:32px;'>
                    Cześć <strong style='color:#fff;'>{$user_imie}</strong>! Twoje bilety zostały zarezerwowane.
                </p>

                <div style='background:#1e1e1e;border-radius:14px;padding:28px;margin-bottom:24px;border-left:4px solid #e63946;'>
                    <h2 style='font-size:20px;margin:0 0 20px;'>{$event_name}</h2>
                    <table style='width:100%;border-collapse:collapse;font-size:14px;'>
                        <tr><td style='color:#666;padding:8px 0;'>📍 Miejsce</td><td style='color:#fff;text-align:right;'>{$venue}, {$miasto}</td></tr>
                        <tr><td style='color:#666;padding:8px 0;'>📅 Data</td><td style='color:#fff;text-align:right;'>{$data}</td></tr>
                        <tr><td style='color:#666;padding:8px 0;'>🎫 Strefa</td><td style='color:#fff;text-align:right;'>{$strefaLabel}</td></tr>
                        <tr><td style='color:#666;padding:8px 0;'>🔢 Liczba biletów</td><td style='color:#fff;text-align:right;'>{$ilosc}</td></tr>
                        <tr style='border-top:1px solid #333;'>
                            <td style='color:#fff;padding:14px 0 0;font-weight:700;font-size:16px;'>Suma</td>
                            <td style='color:#e63946;text-align:right;padding-top:14px;font-weight:700;font-size:20px;'>{$suma} zł</td>
                        </tr>
                    </table>
                </div>

                <div style='background:#1a1a1a;border-radius:10px;padding:18px 24px;font-size:12px;color:#666;line-height:1.7;'>
                    Nr zamówienia: <strong style='color:#fff;'>#{$order_id}</strong><br>
                    Bilety zostaną przesłane na ten adres email przed wydarzeniem.
                </div>

                <p style='margin-top:32px;font-size:12px;color:#444;text-align:center;'>
                    Dziękujemy za zakup! Do zobaczenia na koncercie 🤘
                </p>
            </div>
        </div>
    </body>
    </html>
    ";

    // nagłówki email
    $headers  = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "From: Rockhub <noreply@rockhub.pl>\r\n";
    $headers .= "Reply-To: noreply@rockhub.pl\r\n";

    $mailSent = @mail($user_email, $subject, $message, $headers);

    echo json_encode([
        'success'   => true,
        'message'   => 'Zamówienie złożone! Potwierdzenie wysłano na ' . $user_email,
        'order_id'  => $order_id,
        'mail_sent' => $mailSent
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Błąd serwera: ' . $e->getMessage()]);
}