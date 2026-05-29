<?php
// ============================================================
//  event.php – dane pojedynczego koncertu po id
//  GET: ?id=5
//  Zwraca JSON z v_events_full
// ============================================================

header('Content-Type: application/json; charset=utf-8');
error_reporting(0);
require_once 'BazaDanych.php';

$id = (int)($_GET['id'] ?? 0);

if ($id < 1) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Brak id.']);
    exit;
}

try {
    $pdo  = getConnection();
    $stmt = $pdo->prepare('SELECT * FROM v_events_full WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    $event = $stmt->fetch();

    if (!$event) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Koncert nie istnieje.']);
        exit;
    }

    // podobne – ten sam gatunek, inny id
    $stmt2 = $pdo->prepare(
        'SELECT * FROM v_events_full WHERE gatunek = ? AND id != ? ORDER BY data_czas LIMIT 3'
    );
    $stmt2->execute([$event['gatunek'], $id]);
    $similar = $stmt2->fetchAll();

    echo json_encode([
        'success' => true,
        'event'   => $event,
        'similar' => $similar,
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
