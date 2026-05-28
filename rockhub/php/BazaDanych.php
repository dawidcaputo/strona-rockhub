<?php
// ============================================================
//  BazaDanych.php – połączenie z bazą MySQL
// ============================================================

define('DB_HOST', 'localhost');
define('DB_USER', 'root');        // ← zmień na swojego użytkownika MySQL
define('DB_PASS', '');            // ← zmień na swoje hasło MySQL
define('DB_NAME', 'ticketstobbhell');

function getConnection(): PDO {
    static $pdo = null;

    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    }

    return $pdo;
}