<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';

function getPdoConnection(): PDO
{
    static $connection = null;

    if ($connection instanceof PDO) {
        return $connection;
    }

    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';

    try {
        $connection = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    } catch (PDOException $exception) {
        error_log('Balochi Dastkar database connection failed: ' . $exception->getMessage());
        throw new RuntimeException('The database is temporarily unavailable. Please try again later.');
    }

    return $connection;
}
