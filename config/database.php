<?php

function ph_config(): array
{
    static $config;
    if ($config === null) {
        $config = require __DIR__ . '/config.php';
    }
    return $config;
}

function ph_database_settings(): array
{
    $c = ph_config();
    $databaseUrl = trim((string) (getenv('DATABASE_URL') ?: getenv('POSTGRES_URL') ?: ''));

    if ($databaseUrl !== '') {
        $parts = parse_url($databaseUrl);
        if (
            $parts === false
            || !in_array(strtolower((string) ($parts['scheme'] ?? '')), ['postgres', 'postgresql'], true)
            || empty($parts['host'])
            || empty($parts['user'])
            || !isset($parts['pass'])
            || empty($parts['path'])
        ) {
            throw new RuntimeException('DATABASE_URL must be a PostgreSQL connection URL.');
        }

        $host = (string) $parts['host'];
        $port = (int) ($parts['port'] ?? 5432);
        $database = rawurldecode(ltrim($parts['path'], '/'));
        $user = rawurldecode($parts['user']);
        $password = rawurldecode($parts['pass']);
    } else {
        $host = trim((string) ($c['db_host'] ?? ''));
        $port = (int) ($c['db_port'] ?? 5432);
        $database = trim((string) ($c['db_name'] ?? ''));
        $user = trim((string) ($c['db_user'] ?? ''));
        $password = (string) ($c['db_pass'] ?? '');
    }

    if (
        !preg_match('/^[A-Za-z0-9._:-]+$/', $host)
        || $port < 1
        || $port > 65535
        || !preg_match('/^[A-Za-z0-9_-]+$/', $database)
        || $user === ''
        || $password === ''
    ) {
        throw new RuntimeException('Configure a valid PostgreSQL DATABASE_URL or DB_HOST, DB_PORT, DB_NAME, DB_USER, and DB_PASSWORD.');
    }

    return [
        'dsn' => 'pgsql:host=' . $host . ';port=' . $port . ';dbname=' . $database . ';sslmode=require',
        'user' => $user,
        'password' => $password,
    ];
}

function ph_database_dsn(): string
{
    return ph_database_settings()['dsn'];
}

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    if (!in_array('pgsql', PDO::getAvailableDrivers(), true)) {
        throw new RuntimeException('The PostgreSQL PDO driver (pdo_pgsql) is required for this app.');
    }

    $settings = ph_database_settings();
    $pdo = new PDO($settings['dsn'], $settings['user'], $settings['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    ph_ensure_email_columns($pdo);
    return $pdo;
}

function ph_ensure_email_columns(PDO $pdo): void
{
    static $done = false;
    if ($done) {
        return;
    }

    $cols = $pdo->query(
        "SELECT column_name FROM information_schema.columns
         WHERE table_schema = current_schema() AND table_name = 'users'"
    )->fetchAll(PDO::FETCH_COLUMN);
    if (!$cols) {
        return;
    }

    if (!in_array('email_verified_at', $cols, true)) {
        $pdo->exec('ALTER TABLE users ADD COLUMN email_verified_at TIMESTAMP NULL');
    }
    if (!in_array('email_verify_hash', $cols, true)) {
        $pdo->exec('ALTER TABLE users ADD COLUMN email_verify_hash VARCHAR(64) DEFAULT NULL');
    }
    if (!in_array('email_verify_expires', $cols, true)) {
        $pdo->exec('ALTER TABLE users ADD COLUMN email_verify_expires TIMESTAMP NULL');
    }

    $done = true;
}
