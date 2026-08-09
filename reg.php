<?php
session_start();
include 'conn.php';

mysqli_report(MYSQLI_REPORT_OFF); // don't let a failed query throw and skip our error handling

$upi        = trim($_POST['UPI'] ?? '');
$assessment = trim($_POST['Assesment'] ?? '');
$firstName  = trim($_POST['firstName'] ?? '');
$middleName = trim($_POST['middleName'] ?? '');
$surname    = trim($_POST['surname'] ?? '');
$birthNo    = trim($_POST['birthNo'] ?? '');
$dobRaw     = trim($_POST['DOB'] ?? '');
$gradeRaw   = trim($_POST['Grade'] ?? '');

/* ── Validation — matches the rule documented in bulk_register.php:
   only firstName is required, everything else is optional. ── */
if ($firstName === '') {
    header('Location: RegisterStudent.php?error=1');
    exit;
}

$dob   = $dobRaw !== '' ? $dobRaw : null;
$grade = $gradeRaw !== '' ? (int)$gradeRaw : null;

$stmt = $conn->prepare(
    "INSERT INTO Student (UPI, Assesment, firstName, middleName, surname, DOB, Grade, birthNo, role)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'user')"
);

if (!$stmt) {
    error_log('reg.php prepare failed: ' . $conn->error);
    header('Location: RegisterStudent.php?error=1');
    exit;
}

$stmt->bind_param(
    'ssssssis',
    $upi, $assessment, $firstName, $middleName,
    $surname, $dob, $grade, $birthNo
);

if ($stmt->execute()) {
    $_SESSION['firstName'] = $firstName;
    $stmt->close();
    $conn->close();
    header('Location: RegisterStudent.php?success=1');
    exit;
} else {
    error_log('reg.php insert failed: ' . $stmt->error);
    $stmt->close();
    $conn->close();
    header('Location: RegisterStudent.php?error=1');
    exit;
}