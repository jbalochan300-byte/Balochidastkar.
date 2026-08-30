<?php

require_once __DIR__ . '/../config/database.php';

function getDatabaseConnection(): PDO
{
    return getPdoConnection();
}
