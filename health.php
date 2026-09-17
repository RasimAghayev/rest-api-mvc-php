<?php
/**
 * Health check endpoint — used by the Dockerfile HEALTHCHECK (via cgi-fcgi)
 * and by nginx's own healthcheck (proxied over HTTP).
 *
 * N1 fix (DEC-R1-001): version is no longer hardcoded ('1.0.0'). It comes
 * from the APP_VERSION build ARG/env (set at image build time, e.g. to the
 * git short SHA) — see Dockerfile. Falls back to 'unknown' outside Docker.
 */

header('Content-Type: application/json');

$version = getenv('APP_VERSION') ?: 'unknown';

$dbOk = false;
$dbError = null;
try {
    require __DIR__ . '/app/config/config.php';
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . CHARSET,
        DB_USER,
        DB_PASS,
        [PDO::ATTR_TIMEOUT => 2, PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    $dbOk = true;
} catch (Throwable $e) {
    // Deliberately no exception detail in the response body — this is an
    // unauthenticated endpoint, don't leak DB host/credentials/schema hints.
    $dbError = 'unreachable';
}

http_response_code($dbOk ? 200 : 503);
echo json_encode([
    'status'  => $dbOk ? 'ok' : 'degraded',
    'service' => 'rest-api-mvc-php',
    'version' => $version,
    'db'      => $dbOk ? 'ok' : $dbError,
    'time'    => date('c'),
]);
