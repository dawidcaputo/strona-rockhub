<?php
// ============================================================
//  szukaj.php – wyszukiwanie koncertów
//  Odbiera: ?q=tekst&miasto=Kraków&data=2025-08-15
//  Zwraca JSON: { "success": true, "wyniki": [...] }
// ============================================================

error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json; charset=utf-8');

require_once 'BazaDanych.php';

$q      = trim($_GET['q']      ?? '');
$miasto = trim($_GET['miasto'] ?? '');
$data   = trim($_GET['data']   ?? '');

// buduj zapytanie dynamicznie
$where  = [];
$params = [];

if ($q !== '') {
    $where[]  = '(a.nazwa LIKE ? OR e.nazwa LIKE ? OR a.gatunek LIKE ?)';
    $params[] = "%$q%";
    $params[] = "%$q%";
    $params[] = "%$q%";
}

if ($miasto !== '' && $miasto !== 'Miasto') {
    $where[]  = 'v.miasto = ?';
    $params[] = $miasto;
}

if ($data !== '') {
    $where[]  = 'DATE(e.data_czas) = ?';
    $params[] = $data;
}

$sql = '
    SELECT
        e.id,
        e.nazwa        AS koncert,
        a.nazwa        AS artysta,
        a.gatunek,
        v.nazwa        AS venue,
        v.miasto,
        e.data_czas,
        e.plakat_url,
        e.cena_stojace,
        e.cena_siedzace
    FROM events e
    JOIN artists a ON e.artist_id = a.id
    JOIN venues  v ON e.venue_id  = v.id
';

if ($where) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}

$sql .= ' ORDER BY e.data_czas ASC LIMIT 20';

try {
    $pdo  = getConnection();
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $wyniki = $stmt->fetchAll();

    echo json_encode([
        'success' => true,
        'count'   => count($wyniki),
        'wyniki'  => $wyniki,
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
