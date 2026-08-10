<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

require __DIR__ . '/../auth_check.php';
require __DIR__ . '/_config.php';

function respond($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data);
    exit;
}

$session = require_auth();
if (!in_array($session['role'], ['teacher', 'hoi'], true)) {
    respond(['success' => false, 'message' => 'Not authorized.'], 403);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(['success' => false, 'message' => 'POST required'], 405);
}

$grade = trim($_POST['grade'] ?? '');
if ($grade === '') {
    respond(['success' => false, 'message' => 'grade is required'], 400);
}

respond([
    'success'  => true,
    'grade'    => $grade,
    'maxTotal' => skp_max_total($grade),
    'subjects' => skp_subjects_for_grade($grade),
]);