<?php
$databaseUrl = getenv('DATABASE_URL') ?: getenv('POSTGRES_URL') ?: '';
$parsedUrl = $databaseUrl !== '' ? parse_url($databaseUrl) : null;
if (!is_array($parsedUrl)) {
    $parsedUrl = [];
}

return [
    'db_host' => getenv('DB_HOST') ?: ($parsedUrl['host'] ?? ''),
    'db_port' => getenv('DB_PORT') ?: ($parsedUrl['port'] ?? '5432'),
    'db_name' => getenv('DB_NAME') ?: (($parsedUrl['path'] ?? '') !== '' ? rawurldecode(ltrim($parsedUrl['path'], '/')) : ''),
    'db_user' => getenv('DB_USER') ?: (($parsedUrl['user'] ?? '') !== '' ? rawurldecode($parsedUrl['user']) : ''),
    'db_pass' => getenv('DB_PASSWORD') ?: (getenv('DB_PASS') ?: (($parsedUrl['pass'] ?? '') !== '' ? rawurldecode($parsedUrl['pass']) : '')),
    'session_idle_seconds' => 1800,
    'super_admin_idle_seconds' => 900,
    'mhm_flag_threshold' => 40,
    'mentee_min_age' => 18,
    'mentee_max_age' => 24,
    'app_url' => getenv('APP_URL') ?: '',
    'mail_from' => getenv('MAIL_FROM') ?: 'noreply@phlourish.test',
    'mail_from_name' => getenv('MAIL_FROM_NAME') ?: 'PHLOURISH',
    'signaling_ws_url' => getenv('SIGNALING_WS_URL') ?: 'ws://localhost:8080',
];
