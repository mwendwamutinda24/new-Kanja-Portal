<?php
mysqli_report(MYSQLI_REPORT_OFF);

$conn = mysqli_init();

$caCertPath = __DIR__ . '/ca.pem'; // adjust path to wherever you upload it

mysqli_ssl_set($conn, null, null, $caCertPath, null, null);

$connected = mysqli_real_connect(
    $conn,
    getenv('DB_HOST') ?: 'mysql-12cba62a-mwendwamutinda24-ae64.a.aivencloud.com',
    getenv('DB_USER') ?: 'avnadmin',
    getenv('DB_PASS') ?: 'AVNS_QfHgatItMfKzmXA3kzp',
    getenv('DB_NAME') ?: 'defaultdb',
    getenv('DB_PORT') ? (int) getenv('DB_PORT') : 28692,
    null,
    MYSQLI_CLIENT_SSL
);

if (!$connected) {
    error_log('DB connection failed: ' . mysqli_connect_error());
    http_response_code(500);
    die('<div style="font-family:sans-serif;max-width:480px;margin:4rem auto;padding:1.5rem 1.8rem;border-radius:10px;background:#fff0f0;border-left:4px solid #e24b4a;color:#c0392b">
        <strong>We could not connect to the database.</strong><br>
        Please try again in a moment. If this keeps happening, contact the school office.
    </div>');
}
