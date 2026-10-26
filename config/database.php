<?php
function config(): array
{
    return [
        'host' => '127.0.0.1',
        'port' => '3306',
        'database' => 'cuyotech_ssis_db',
        'username' => 'root',
        'password' => '',
        'base_url' => '/CuyoTech-SSIS',
        'campus_image' => '',
        'grade_values' => [],
    ];
}

function db(): PDO
{
    static $pdo;
    if (!$pdo) {
        $c = config();
        $pdo = new PDO(
            "mysql:host={$c['host']};port={$c['port']};dbname={$c['database']}",
            $c['username'],
            $c['password'],
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        );
        $pdo->exec("SET time_zone = '+00:00'");
    }
    return $pdo;
}
