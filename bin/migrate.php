<?php

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/../config/database.php';

$schemaPath = __DIR__ . '/../sql/schema.sql';
$schema = file_get_contents($schemaPath);
if ($schema === false) {
    throw new RuntimeException('Unable to read database schema: ' . $schemaPath);
}

$statements = preg_split('/;\s*(?:\r?\n|$)/', $schema);
if ($statements === false || !$statements) {
    throw new RuntimeException('Unable to parse the database schema.');
}

$pdo = db();
$pdo->beginTransaction();
try {
    foreach ($statements as $statement) {
        $statement = trim($statement);
        if ($statement !== '') {
            $pdo->exec($statement);
        }
    }
    $pdo->commit();
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    throw $e;
}

fwrite(STDOUT, "Database schema is ready.\n");
