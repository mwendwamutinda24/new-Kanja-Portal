<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Grade 7 Fee Payment Report</title>
    <link rel="stylesheet" href="index.css">
</head>
<body>
<div class="marks">
    <h1>Stephen Kanja Primary and Junior School</h1>
    <h2>Grade 1 Fee Report</h2>
</div>

<div class="result1">
    <form method="GET" action="">
        <label>Select Term:</label>
        <select name="term2" required>
            <option value="">--Select Term--</option>
            <option value="Term 1">Term 1</option>
            <option value="Term 2">Term 2</option>
            <option value="Term 3">Term 3</option>
        </select>

        <label>Select Year:</label>
        <select name="year" required>
            <option value="">--Select Year--</option>
            <option value="2026">2026</option>
            <option value="2027">2027</option>
            <option value="2028">2028</option>
            <option value="2029">2029</option>
            <option value="2030">2030</option>
        </select>

        <button class="btn10">View Fee Statement</button>
    </form>

    <table>
        <tr>
            <th>Assessment No</th>
            <th>First Name</th>
            <th>Surname</th>
            <th>PTA Fee Paid</th>
            <th>Assessment</th>
            <th>Project</th>
            <th>Other</th>
            <th>Total</th>
            <th>Balance</th>
        </tr>

        <?php
        include 'conn.php';

        $term = $_GET['term2'] ?? '';
        $year = $_GET['year'] ?? '';

        if ($term && $year) {
            $term = mysqli_real_escape_string($conn, trim($term));
            $year = mysqli_real_escape_string($conn, trim($year));

            $sql = "SELECT * FROM fee WHERE grade =1 AND term = '$term' AND year = '$year'";
            $result = mysqli_query($conn, $sql);

            if ($result && mysqli_num_rows($result) > 0) {
                while ($row = mysqli_fetch_assoc($result)) {
                    $pta = (int)$row['pta'];
                    $exam = (int)$row['exam'];
                    $project = (int)$row['project'];
                    $other = (int)$row['other'];
                    $expected = isset($row['expected']) ? (int)$row['expected'] : 0;

                    $total = $pta + $exam + $project + $other;
                    $balance = $expected - $total;

                    echo "
                    <tr>
                        <td>{$row['assesment']}</td>
                        <td>{$row['firstName']}</td>
                        <td>{$row['surname']}</td>
                        <td>{$pta}</td>
                        <td>{$exam}</td>
                        <td>{$project}</td>
                        <td>{$other}</td>
                        <td>{$total}</td>
                        <td>{$balance}</td>
                    </tr>";
                }
            } else {
                echo "<tr><td colspan='9' style='text-align:center; color:red;'>No records found for Grade 1 in $term, $year.</td></tr>";
            }
        } else {
            echo "<tr><td colspan='9' style='text-align:center; color:red;'>Please select both term and year to view the report.</td></tr>";
        }
        ?>
    </table>
</div>
</body>
</html>
