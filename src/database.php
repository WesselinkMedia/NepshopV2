<?php

function env_value(string $key): string
{
    static $env = null;

    if ($env === null) {
        $file = dirname(__DIR__) . '/.env';
        if (!is_file($file)) {
            throw new RuntimeException('Missing .env file');
        }

        $parsed = parse_ini_file($file, false, INI_SCANNER_RAW);
        if ($parsed === false) {
            throw new RuntimeException('Could not read .env file');
        }

        $env = $parsed;
    }

    return trim((string) ($env[$key] ?? ''));
}

function db(): mysqli
{
    static $connection = null;

    if ($connection instanceof mysqli) {
        return $connection;
    }

    $username = env_value('MARIADB_USERNAME');
    $name = env_value('DB_NAME');
    if ($username === '' || $name === '') {
        throw new RuntimeException('Missing database configuration');
    }

    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    $connection = mysqli_connect('127.0.0.1', $username, env_value('MARIADB_PASSWORD'), $name);
    $connection->set_charset('utf8mb4');

    return $connection;
}

function db_row(mysqli $db, string $sql, string $types, int|string ...$params): ?array
{
    $row = db_run($db, $sql, $types, $params)->get_result()->fetch_assoc();

    return $row ?: null;
}

function db_exec(mysqli $db, string $sql, string $types, int|string ...$params): void
{
    db_run($db, $sql, $types, $params);
}

function db_run(mysqli $db, string $sql, string $types, array $params): mysqli_stmt
{
    $stmt = $db->prepare($sql);
    if ($types !== '') {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();

    return $stmt;
}
