<?php
include 'conn.php';

ini_set('display_errors', 1);
error_reporting(E_ALL);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

if (!empty($_POST['firstName'])) {
    foreach ($_POST['firstName'] as $i => $fname) {
    
        $assessment     = $_POST['assessment'][$i];
        $surname        = $_POST['surname'][$i];
        $fee            = $_POST['Fee'][$i];
        $assessmentFee  = $_POST['AssessmentFee'][$i];
        $activity       = $_POST['Activity'][$i];
        $other          = $_POST['Other'][$i];
        $grade          = $_POST['grade'];
        $term           = $_POST['term'];
        $year           = $_POST['year'];

   
        if (
            empty($fee) &&
            empty($assessmentFee) &&
            empty($activity) &&
            empty($other)
        ) {
            continue; 
        }
        $sql = "INSERT INTO Fees 
        (Assesment, firstName, surname, Fee, AssesmentFee, Activity, Other, Grade, Term, Year, payment_date)
        VALUES ('$assessment','$fname','$surname','$fee','$assessmentFee','$activity','$other','$grade','$term','$year', NOW())";


        if (!mysqli_query($conn, $sql)) {
            echo "Error: " . mysqli_error($conn) . "<br>Query: " . $sql;
        }
    }

    echo "<script>
        alert('Fees Paid Successfully');
        window.location.href='Hoi.php';
    </script>";
} else {
    echo "No student data submitted.";
}

?>
