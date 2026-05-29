<?php
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
    $pdo = getConnection();
 
    $stmt = $pdo->prepare('
        SELECT
            e.id,
            e.nazwa         AS koncert,
            e.opis,
            e.data_czas,
            COALESCE(e.plakat_url, a.zdjecie_url) AS plakat_url,
            e.cena_siedzace,
            e.cena_stojace,
            e.miejsca_siedzace,
            e.miejsca_stojace,
            a.nazwa         AS artysta,
            a.gatunek,
            v.nazwa         AS miejsce,
            v.miasto,
            v.adres
        FROM events e
        JOIN artists a ON e.artist_id = a.id
        JOIN venues  v ON e.venue_id  = v.id
        WHERE e.id = ?
        LIMIT 1
    ');
    $stmt->execute([$id]);
    $event = $stmt->fetch();
 
    if (!$event) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Koncert nie istnieje.']);
        exit;
    }
 
    $stmt2 = $pdo->prepare('
        SELECT
            e.id,
            e.nazwa         AS koncert,
            e.data_czas,
            COALESCE(e.plakat_url, a.zdjecie_url) AS plakat_url,
            a.nazwa         AS artysta,
            a.gatunek,
            v.miasto
        FROM events e
        JOIN artists a ON e.artist_id = a.id
        JOIN venues  v ON e.venue_id  = v.id
        WHERE a.gatunek = ? AND e.id != ?
        ORDER BY e.data_czas
        LIMIT 3
    ');
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