<?php
/**
 * UniHub database connection
 * Local MySQL configuration.
 */

define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3306');
define('DB_USER', 'root');
define('DB_PASS', 'ccdan@2026Learn');
define('DB_NAME', 'unihub');
define('DB_CHARSET', 'utf8mb4');

function db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dsn = 'mysql:host=' . DB_HOST .
           ';port=' . DB_PORT .
           ';dbname=' . DB_NAME .
           ';charset=' . DB_CHARSET;

    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);

    return $pdo;
}