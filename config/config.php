<?php
$databaseUrl = getenv('DATABASE_URL') ?: getenv('POSTGRES_URL') ?: '';
$parsedUrl = $databaseUrl !== '' ? parse_url($databaseUrl) : null;

return [
    'db_host' => ($_ENV['DB_HOST'] ?? getenv('DB_HOST') ?? ($parsedUrl['host'] ?? 'dpg-dbo4rh3l550s738nnpdg-a.singapore-postgres.render.com')),
    'db_port' => ($_ENV['DB_PORT'] ?? getenv('DB_PORT') ?? ($parsedUrl['port'] ?? '5432')),
    'db_name' => ($_ENV['DB_NAME'] ?? getenv('DB_NAME') ?? (($parsedUrl['path'] ?? '') !== '' ? ltrim($parsedUrl['path'], '/') : 'phlourish')),
    'db_user' => ($_ENV['DB_USER'] ?? getenv('DB_USER') ?? ($parsedUrl['user'] ?? 'phlourish_user')),
    'db_pass' => ($_ENV['DB_PASSWORD'] ?? getenv('DB_PASSWORD') ?? $_ENV['DB_PASS'] ?? getenv('DB_PASS') ?? ($parsedUrl['pass'] ?? '')),
    'session_idle_seconds' => 1800,
    'super_admin_idle_seconds' => 900,
    'mhm_flag_threshold' => 40,
    'mentee_min_age' => 18,
    'mentee_max_age' => 24,
    'app_url' => ($_ENV['APP_URL'] ?? getenv('APP_URL') ?? ''),
    'mail_from' => ($_ENV['MAIL_FROM'] ?? getenv('MAIL_FROM') ?? 'noreply@phlourish.test'),
    'mail_from_name' => ($_ENV['MAIL_FROM_NAME'] ?? getenv('MAIL_FROM_NAME') ?? 'PHLOURISH'),
    'signaling_ws_url' => ($_ENV['SIGNALING_WS_URL'] ?? getenv('SIGNALING_WS_URL') ?? 'ws://localhost:8080'),
];
