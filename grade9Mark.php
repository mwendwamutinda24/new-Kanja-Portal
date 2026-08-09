<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Results</title>
    <link rel="stylesheet" href="index.css">
</head>
<body>

<header class="page-header">
    <h1>Stephen Kanja Primary & Junior School</h1>
    <p class="subtitle">Academic Performance Transcript</p>
</header>

<div class="results">
    <form method="GET" action="" class="filter-form">
      <div class="form-group">
            <label>Select Term:</label>
                <br/>
            <select name="grade" required>
                <option value="">--SelectGrade--</option>
                <option value="Grade 1">Grade 1</option>
                <option value="Grade 2">Grade 2</option>
                <option value="Grade 3">Grade 3</option>
                 <option value="Grade 4">Grade 4</option>
                 <option value="Grade 5">Grade 5</option>
                 <option value="Grade 6">Grade 6</option>
                 <option value="Grade 7">Grade 7</option>
                 <option value="Grade 8">Grade 8</option>
                 <option value="Grade 9">Grade 9</option>
            </select>
        </div>

        <div class="form-group">
            <label>Select Term:</label>
                <br/>
            <select name="term" required>
                <option value="">--Select Term--</option>
                <option value="Term 1">Term 1</option>
                <option value="Term 2">Term 2</option>
                <option value="Term 3">Term 3</option>
            </select>
        </div>

        <div class="form-group">
            <label>Select Exam Type:</label>
                <br/>
            <select name="exam_type" required>
                <option value="">--Select Type--</option>
                <option value="opener">Opener</option>
                <option value="mid-term">Mid-Term</option>
                <option value="end-term">End Term</option>
            </select>
        </div>

        <div class="form-group">
            <label>Select Year:</label>
                <br/>
            <select name="year" required>
                <option value="">--Select Year--</option>
                <option value="2026">2026</option>
                <option value="2027">2027</option>
                <option value="2028">2028</option>
                <option value="2029">2029</option>
                <option value="2030">2030</option>
            </select>
        </div>

        <button type="submit" class="btn-view">View Results</button>
    </form>

    <table class="results-table">
        <thead>
            <tr>
                <th>Rank</th>
                <th>Assessment No</th>
                <th>First Name</th>
                <th>Surname</th>
                <th>Maths</th>
                <th>Eng</th>
                <th>Kisw</th>
                <th>S/ST</th>
                <th>Scie</th>
                <th>C/A</th>
                <th>Agri</th>
                <th>RE</th>
                <th>Pretec</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
<?php
include 'conn.php';

function getAward($score) {
    if ($score > 74) return "<span class='award ee'>E.E</span>";
    elseif ($score > 49) return "<span class='award me'>M.E</span>";
    elseif ($score > 25) return "<span class='award ae'>A.E</span>";
    else return "<span class='award be'>B.E</span>";
}

function getTotalAward($score1) {
    if ($score1 > 725) return "<span class='award ee'>E.E</span>";
    elseif ($score1 > 450) return "<span class='award me'>M.E</span>";
    elseif ($score1 > 225) return "<span class='award ae'>A.E</span>";
    else return "<span class='award be'>B.E</span>";
}

$termLabel = $_GET['term'] ?? '';
$examLabel = $_GET['exam_type'] ?? '';
$year = $_GET['year'] ?? '';

$termMap = ['Term 1' => '1', 'Term 2' => '2', 'Term 3' => '3'];
$term = $termMap[$termLabel] ?? $termLabel;

$examMap = ['opener' => 'opener', 'mid-term' => 'midterm', 'end-term' => 'endterm'];
$exam_type = $examMap[$examLabel] ?? $examLabel;

if ($term && $exam_type && $year) {
    $sql = "SELECT DISTINCT * FROM exam2 
            WHERE grade=9 AND term='$term' AND exam_type='$exam_type' AND year='$year'";
    $result = mysqli_query($conn, $sql);

    $students = [];

    if (mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $math    = (int)$row['math'];
            $eng     = (int)$row['eng'];
            $kisw    = (int)$row['kisw'];
            $sst     = (int)$row['sst'];
            $scie    = (int)$row['scie'];
            $ca      = (int)$row['ca'];
            $agri    = (int)$row['agri'];
            $re      = (int)$row['re'];
            $pretec  = (int)$row['pretec'];

            $total = $math + $eng + $kisw + $sst + $scie + $ca + $agri + $re + $pretec;

            $row['total'] = $total;
            $row['subjects'] = [
                'math' => $math, 'eng' => $eng, 'kisw' => $kisw, 'sst' => $sst,
                'scie' => $scie, 'ca' => $ca, 'agri' => $agri, 're' => $re, 'pretec' => $pretec
            ];

            $students[] = $row;
        }

        usort($students, function($a, $b) {
            return $b['total'] <=> $a['total'];
        });

        $rank = 1;
        $prevTotal = null;
        $repeatCount = 0;

        foreach ($students as $i => $student) {
            if ($student['total'] === $prevTotal) {
                $students[$i]['rank'] = $rank;
                $repeatCount++;
            } else {
                $rank += $repeatCount;
                $students[$i]['rank'] = $rank;
                $prevTotal = $student['total'];
                $repeatCount = 1;
            }
        }

        foreach ($students as $student) {
            echo "<tr>
                <td>{$student['rank']}</td>
                <td>{$student['Assesment']}</td>
                <td>{$student['firstName']}</td>
                <td>{$student['lastName']}</td>";

            foreach ($student['subjects'] as $subject => $score) {
                echo "<td>{$score} " . getAward($score) . "</td>";
            }

            echo "<td>{$student['total']} " . getTotalAward($student['total']) . "</td>
            </tr>";
        }
    } else {
        echo "<tr><td colspan='14' class='no-results'>No results found for selected filters.</td></tr>";
    }
} else {
    echo "<tr><td colspan='14' class='no-results'>Please select term, exam type, and year to view results.</td></tr>";
}
?>
        </tbody>
    </table>
</div>

</body>
</html>
