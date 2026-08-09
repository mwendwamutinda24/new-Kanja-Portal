<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Results</title>
    <link rel="stylesheet" href="index.css">
</head>
<body>

<div class="marks">
    <h2>Stephen Kanja Primary and Junior School</h2>
    <p>School Transcript for Grade 4</p>
</div>

<div class="results">
    <form method="GET" action="">
        <label>Select Term:</label>
        <select name="term" required>
            <option value="" required>--Select Term--</option>
            <option value="Term 1">Term 1</option>
            <option value="Term 2">Term 2</option>
            <option value="Term 3">Term 3</option>
        </select>

        <label>Select Exam Type:</label>
        <select name="exam_type" required>
            <option value="" required>--Select Type--</option>
            <option value="opener">Opener</option>
            <option value="mid-term">Mid-Term</option>
            <option value="end-term">End Term</option>
            <Option value="other">Other</Option>
        </select>

        <label>Select Year:</label>
        <select name="year" required>
            <option value="" required>--Select Year--</option>
            <option value="2026">2026</option>
            <option value="2027">2027</option>
            <option value="2028">2028</option>
            <option value="2029">2029</option>
            <option value="2030">2030</option>
        </select>

        <button type="submit" class="btn4">View Results</button>
    </form>

    <table>
        <tr>
            <th>RANK</th>
            <th>Assessment No</th>
            <th>First Name</th>
            <th>Surname</th>
            <th>Maths</th>
            <th>Eng</th>
            <th>Kisw</th>
            <th>S/ST</th>
            <th>Scie</th>
            <th>C/A</th>
            <th>AGRI</th>
            <th>TOTAL</th>
          
        </tr>

   <?php
include 'conn.php';

function getAward($score) {
    if ($score > 74) return "E.E";
    elseif ($score > 49) return "M.E";
    elseif ($score > 25) return "A.E";
    else return "B.E";
}

function getTotalAward($score) {
    if ($score > 525) return "E.E";      
    elseif ($score > 343) return "M.E";   
    elseif ($score > 175) return "A.E";  
    else return "B.E";
}

$term = $_GET['term'] ?? '';
$exam_type = $_GET['exam_type'] ?? '';
$year = $_GET['year'] ?? '';

if ($term && $exam_type && $year) {
    $sql = "SELECT DISTINCT * FROM exam WHERE grade=4 AND term='$term' AND exam_type='$exam_type' AND year='$year'";
    $result = mysqli_query($conn, $sql);

    $students = [];

    if (mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $scores = [
                'math' => (int)$row['math'],
                'eng' => (int)$row['eng'],
                'kisw' => (int)$row['kisw'],
                'sst' => (int)$row['sst'],
                'scie' => (int)$row['scie'],
                'ca' => (int)$row['ca'],
                'agri' => (int)$row['agri']
            ];

            $total = array_sum($scores);

            $row['total'] = $total;
            $row['subjects'] = $scores;
            $students[] = $row;
        }

        usort($students, fn($a, $b) => $b['total'] <=> $a['total']);

        $rank = 1;
        $prevTotal = null;
        $tieCount = 0;

        foreach ($students as $i => $student) {
            if ($student['total'] === $prevTotal) {
                $students[$i]['rank'] = $rank;
                $tieCount++;
            } else {
                $rank += $tieCount;
                $students[$i]['rank'] = $rank;
                $prevTotal = $student['total'];
                $tieCount = 1;
            }
        }

        foreach ($students as $student) {
            echo "<tr>
                <td>{$student['rank']}</td>
                <td>{$student['Assesment']}</td>
                <td>{$student['firstName']}</td>
                <td>{$student['lastName']}</td>";

            foreach ($student['subjects'] as $score) {
                echo "<td>{$score} " . getAward($score) . "</td>";
            }

            echo "<td>{$student['total']} " . getTotalAward($student['total']) . "</td>
            </tr>";
        }
    } else {
        echo "<tr><td colspan='13' style='text-align:center;'>No results found for selected filters.</td></tr>";
    }
} else {
    echo "<tr><td colspan='13' style='text-align:center; color:red;'>Please select term, exam type, and year to view results.</td></tr>";
}
?>


    </table>
</div>

</body>
</html>
