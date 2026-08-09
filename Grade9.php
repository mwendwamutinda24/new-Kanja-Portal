<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Students</title>
  <link rel="stylesheet" href="index.css">
  <style>
    body {
      font-family: Arial, sans-serif;
      margin: 20px;
      background: #f9f9f9;
    }
    .header3 {
      text-align: center;
      margin-bottom: 20px;
    }
    table {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 15px;
      background: #fff;
      box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }
    th, td {
      border: 1px solid #ddd;
      padding: 10px;
      text-align: center;
    }
    th {
      background-color: #0073e6;
      color: white;
    }
    tr:nth-child(even) {
      background-color: #f2f2f2;
    }
    .count-box {
      font-size: 18px;
      font-weight: bold;
      text-align: right;
      margin-top: 10px;
      color: #333;
    }
  </style>
</head>
<body>
  <div class="header3">
    <h1>STEPHEN KANJA PRIMARY AND JUNIOR SCHOOL</h1>
    <h2>SCHOOL ENROLMENT DATA</h2>
    <h1>Grade 8</h1>
  </div>

  <?php
  include 'conn.php';
  $sql = "SELECT DISTINCT * FROM Student WHERE Grade = 9";
  $result = mysqli_query($conn, $sql);

  $index = 1;
  $count = mysqli_num_rows($result); // total learners

  echo "
  <table>
    <thead>
      <tr>
        <th>Index</th>
        <th>UPI</th>
        <th>Assessment No</th>
        <th>First Name</th>
        <th>Other Name</th>
        <th>Surname</th>
        <th>Birth Certificate No</th>
        <th>Date Of Birth</th>
      </tr>
    </thead>
    <tbody>
  ";

  while ($row = mysqli_fetch_assoc($result)) {
    echo "
      <tr>
        <td>{$index}</td>
        <td>{$row['UPI']}</td>
        <td>{$row['Assesment']}</td>
        <td>{$row['firstName']}</td>
        <td>{$row['middleName']}</td>
        <td>{$row['surname']}</td>
        <td>{$row['birthNo']}</td>
        <td>{$row['DOB']}</td>
      </tr>
    ";
    $index++;
  }

  echo "
    </tbody>
  </table>
  <div class='count-box'>Total Learners: {$count}</div>
  ";

  $conn->close();
  ?>
</body>
</html>
