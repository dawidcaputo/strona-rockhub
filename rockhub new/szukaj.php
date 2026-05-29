<?php
header('Content-Type: application/json; charset=utf-8');
error_reporting(0);
require_once 'BazaDanych.php';

$q      = trim($_GET['q']      ?? '');
$miasto = trim($_GET['miasto'] ?? '');
$data   = trim($_GET['data']   ?? '');

try {
    $pdo = getConnection();

    $sql = '
        SELECT
            e.id,
            e.nazwa         AS koncert,
            e.opis,
            e.data_czas,
            COALESCE(e.plakat_url, a.zdjecie_url) AS plakat_url,
            e.cena_siedzace,
            e.cena_stojace,
            a.nazwa         AS artysta,
            a.gatunek,
            v.nazwa         AS miejsce,
            v.miasto
        FROM events e
        JOIN artists a ON e.artist_id = a.id
        JOIN venues  v ON e.venue_id  = v.id
        WHERE 1=1
    ';
    $params = [];

    if ($q !== '') {
        $sql     .= ' AND (e.nazwa LIKE ? OR a.nazwa LIKE ? OR a.gatunek LIKE ?)';
        $like     = "%{$q}%";
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
    }

    if ($miasto !== '') {
        $sql     .= ' AND v.miasto = ?';
        $params[] = $miasto;
    }

    if ($data !== '') {
        $sql     .= ' AND DATE(e.data_czas) = ?';
        $params[] = $data;
    }

    $sql .= ' ORDER BY e.data_czas ASC';

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