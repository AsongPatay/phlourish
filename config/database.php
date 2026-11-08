<?php

function ph_config(): array
{
    static $config;
    if ($config === null) {
        $config = require __DIR__ . '/config.php';
    }
    return $config;
}

function ph_database_dsn(): string
{
    $c = ph_config();
    $host = getenv('DB_HOST') ?: ($_ENV['DB_HOST'] ?? ($c['db_host'] ?? 'dpg-dbo4rh3l550s738nnpdg-a.singapore-postgres.render.com'));
    $port = getenv('DB_PORT') ?: ($_ENV['DB_PORT'] ?? ($c['db_port'] ?? '5432'));
    $dbname = getenv('DB_NAME') ?: ($_ENV['DB_NAME'] ?? ($c['db_name'] ?? 'phlourish'));
    $user = getenv('DB_USER') ?: ($_ENV['DB_USER'] ?? ($c['db_user'] ?? ''));
    $pass = getenv('DB_PASS') ?: ($_ENV['DB_PASS'] ?? ($c['db_pass'] ?? ''));

    $databaseUrl = getenv('DATABASE_URL') ?: getenv('POSTGRES_URL') ?: '';
    if ($databaseUrl !== '') {
        $parsed = parse_url($databaseUrl);
        if (is_array($parsed)) {
            $host = (string) ($parsed['host'] ?? $host);
            $port = (string) ($parsed['port'] ?? $port);
            $dbname = ltrim((string) ($parsed['path'] ?? $dbname), '/');
            $user = (string) ($parsed['user'] ?? $user);
            $pass = (string) ($parsed['pass'] ?? $pass);
        }
    }

    return "pgsql:host={$host};port={$port};dbname={$dbname};sslmode=require";
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

    $host = getenv('DB_HOST') ?: ($_ENV['DB_HOST'] ?? 'dpg-dbo4rh3l550s738nnpdg-a.singapore-postgres.render.com');
    $port = getenv('DB_PORT') ?: ($_ENV['DB_PORT'] ?? '5432');
    $dbname = getenv('DB_NAME') ?: ($_ENV['DB_NAME'] ?? 'phlourish');
    $user = getenv('DB_USER') ?: ($_ENV['DB_USER'] ?? 'phlourish_user');
    $pass = getenv('DB_PASS') ?: ($_ENV['DB_PASS'] ?? 'BvZxienrZs7YVaVjqNZWf8F9dpVMuU8W');

    $dsn = "pgsql:host={$host};port={$port};dbname={$dbname};sslmode=require";

    try {
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    } catch (PDOException $e) {
        die("Database Connection Failed: " . $e->getMessage());
    }

    ph_ensure_email_columns($pdo);
    return $pdo;
}

function ph_ensure_email_columns(PDO $pdo): void
{
    static $done = false;
    if ($done) {
        return;
    }
    try {
        $cols = $pdo->query(
            "SELECT column_name FROM information_schema.columns WHERE table_schema = 'public' AND table_name = 'users'"
        )->fetchAll(PDO::FETCH_COLUMN);

        if (!in_array('email_verified_at', $cols, true)) {
            $pdo->exec('ALTER TABLE student ADD COLUMN email_verified_at TIMESTAMP NULL');
            $pdo->exec('UPDATE student SET email_verified_at = COALESCE(created_at, NOW()) WHERE email_verified_at IS NULL');
        }
        if (!in_array('email_verify_hash', $cols, true)) {
            $pdo->exec('ALTER TABLE student ADD COLUMN email_verify_hash VARCHAR(64) DEFAULT NULL');
        }
        if (!in_array('email_verify_expires', $cols, true)) {
            $pdo->exec('ALTER TABLE student ADD COLUMN email_verify_expires TIMESTAMP NULL');
        }
        $done = true;
    } catch (Throwable $e) {
        // Tables may not exist yet (install.php).
    }
}
