<?php
// ============================================================
//  pobierz_eventy.php – zwraca eventy do siatki jako JSON
// ============================================================

header('Content-Type: application/json; charset=utf-8');
session_start();
require_once 'BazaDanych.php';

try {
    $pdo  = getConnection();
    $stmt = $pdo->query('
        SELECT e.id, e.nazwa, e.cena_stojace, e.plakat_url, a.nazwa AS gatunek, v.miasto
        FROM events e
        JOIN artists a ON e.artist_id = a.id
        JOIN venues  v ON e.venue_id  = v.id
        WHERE e.id BETWEEN 5 AND 13
        ORDER BY e.id
    ');
    $eventy = $stmt->fetchAll();
    echo json_encode(['success' => true, 'eventy' => $eventy]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}