<?php
include 'conn.php';

// Collect shared values
$gradeValue = mysqli_real_escape_string($conn, $_POST['grade']); 
$term = mysqli_real_escape_string($conn, $_POST['term']);
$year = mysqli_real_escape_string($conn, $_POST['year']);

// Collect arrays
$assessments = $_POST['assesment'];
$firstNames = $_POST['firstName'];
$lastNames = $_POST['surname'];
$pta = $_POST['PTA'];
$exam = $_POST['Exam'];
$project = $_POST['Projects'];
$other = $_POST['other'];

$inserted = 0;
$updated = 0;

for ($i = 0; $i < count($firstNames); $i++) {
    $assessment = mysqli_real_escape_string($conn, $assessments[$i]);
    $firstName = mysqli_real_escape_string($conn, $firstNames[$i]);
    $lastName = mysqli_real_escape_string($conn, $lastNames[$i]);
    $ptaMark = mysqli_real_escape_string($conn, $pta[$i]);
    $examMark = mysqli_real_escape_string($conn, $exam[$i]);
    $projectMark = mysqli_real_escape_string($conn, $project[$i]);
    $otherMark = mysqli_real_escape_string($conn, $other[$i]);

    // Skip empty assessment rows
    if (trim($assessment) === '') continue;

    // Check if record exists for this student, term, and year
    $checkQuery = "SELECT * FROM fee WHERE assesment='$assessment' AND term='$term' AND year='$year'";
    $result = mysqli_query($conn, $checkQuery);

    if (mysqli_num_rows($result) > 0) {
        $updateFields = [];

        if ($ptaMark !== '') $updateFields[] = "pta='$ptaMark'";
        if ($examMark !== '') $updateFields[] = "exam='$examMark'";
        if ($projectMark !== '') $updateFields[] = "project='$projectMark'";
        if ($otherMark !== '') $updateFields[] = "other='$otherMark'";
        if ($gradeValue !== '') $updateFields[] = "grade='$gradeValue'";

        if (!empty($updateFields)) {
            $updateQuery = "UPDATE fee SET " . implode(", ", $updateFields) . " 
                            WHERE assesment='$assessment' AND term='$term' AND year='$year'";
            mysqli_query($conn, $updateQuery);
            $updated++;
        }
    } else {
        $insertQuery = "INSERT INTO fee 
            (assesment, firstName, surname, pta, exam, project, other, grade, term, year)
            VALUES 
            ('$assessment', '$firstName', '$lastName', '$ptaMark', '$examMark', '$projectMark', '$otherMark', '$gradeValue', '$term', '$year')";
        mysqli_query($conn, $insertQuery);
        $inserted++;
    }
}

echo "
<script>
    alert('Fee upload complete. Inserted: $inserted, Updated: $updated');
    window.location.href='ViewFee.php';
</script>";
?>
