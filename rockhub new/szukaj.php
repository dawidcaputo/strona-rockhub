<?php
// ============================================================
//  szukaj.php – wyszukiwanie koncertów
//  GET: ?q=nazwa&miasto=Kraków&data=2025-08-15
//  Zwraca JSON: { "success": true, "events": [...] }
// ============================================================
 
header('Content-Type: application/json; charset=utf-8');
error_reporting(0);
 
require_once 'BazaDanych.php';
 
$q      = trim($_GET['q']      ?? '');
$miasto = trim($_GET['miasto'] ?? '');
$data   = trim($_GET['data']   ?? '');
 
try {
    $pdo = getConnection();
 
    // bazowe zapytanie – używamy widoku v_events_full
    $sql    = 'SELECT * FROM v_events_full WHERE 1=1';
    $params = [];
 
    // filtr: fraza (nazwa koncertu LUB artysta LUB gatunek)
    if ($q !== '') {
        $sql     .= ' AND (koncert LIKE ? OR artysta LIKE ? OR gatunek LIKE ?)';
        $like     = "%{$q}%";
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
    }
 
    // filtr: miasto
    if ($miasto !== '') {
        $sql     .= ' AND miasto = ?';
        $params[] = $miasto;
    }
 
    // filtr: data (tylko dzień – ignorujemy godzinę)
    if ($data !== '') {
        $sql     .= ' AND DATE(data_czas) = ?';
        $params[] = $data;
    }
 
    $sql .= ' ORDER BY data_czas ASC';
 
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $events = $stmt->fetchAll();
 
    echo json_encode([
        'success' => true,
        'count'   => count($events),
        'events'  => $events,
    ]);
 
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}