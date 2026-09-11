<?php
mysqli_report(MYSQLI_REPORT_OFF);

$caCertPath = __DIR__ . '/ca.pem'; // adjust path to wherever you upload it

$dbHost = getenv('DB_HOST') ?: 'mysql-12cba62a-mwendwamutinda24-ae64.a.aivencloud.com';
$dbUser = getenv('DB_USER') ?: 'avnadmin';
$dbPass = getenv('DB_PASS'); // no hardcoded fallback — see note below
$dbName = getenv('DB_NAME') ?: 'defaultdb';
$dbPort = getenv('DB_PORT') ? (int) getenv('DB_PORT') : 28692;

if ($dbPass === false) {
    error_log('DB connection failed: DB_PASS environment variable is not set');
    http_response_code(500);
    die('<div style="font-family:sans-serif;max-width:480px;margin:4rem auto;padding:1.5rem 1.8rem;border-radius:10px;background:#fff0f0;border-left:4px solid #e24b4a;color:#c0392b">
        <strong>We could not connect to the database.</strong><br>
        Please try again in a moment. If this keeps happening, contact the school office.
    </div>');
}

$maxAttempts = 3;
$baseDelaySeconds = 1; // doubles each retry: 1s, 2s

$conn = null;
$connected = false;
$lastError = '';

for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
    $conn = mysqli_init();
    mysqli_ssl_set($conn, null, null, $caCertPath, null, null);

    $connected = mysqli_real_connect(
        $conn,
        $dbHost,
        $dbUser,
        $dbPass,
        $dbName,
        $dbPort,
        null,
        MYSQLI_CLIENT_SSL
    );

    if ($connected) {
        break;
    }

    $lastError = mysqli_connect_error();
    error_log("DB connection attempt {$attempt}/{$maxAttempts} failed: {$lastError}");

    if ($attempt < $maxAttempts) {
        sleep($baseDelaySeconds * $attempt); // 1s, then 2s
    }
}

if (!$connected) {
    error_log('DB connection failed after ' . $maxAttempts . ' attempts: ' . $lastError);
    http_response_code(500);
    die('<div style="font-family:sans-serif;max-width:480px;margin:4rem auto;padding:1.5rem 1.8rem;border-radius:10px;background:#fff0f0;border-left:4px solid #e24b4a;color:#c0392b">
        <strong>We could not connect to the database.</strong><br>
        Please try again in a moment. If this keeps happening, contact the school office.
    </div>');
}
