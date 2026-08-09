<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Students</title>
  <link rel="stylesheet" href="index.css">
</head>
<body>
  
<div class="header3">
  <h1>STEPHEN KANJA PRIMARY AND JUNIOR SCHOOL</h1>
 <h2>SCHOOL ENROLMENT DATA</h2>
 <h1>Grade 6</h1>
</div>

    <?php

include 'conn.php';
$sql = "SELECT DISTINCT* FROM Student WHERE Grade =6";
$result = mysqli_query($conn, $sql);

$index=1;

echo "
<table>
  <thead>
    <tr>
      <th>Index</th>
      <th>UPI</th>
      <th>Assesment No</th>
      <th>First Name</th>
      <th>Other Name</th>
      <th>Surname</th>
      <th>Birth Certificate No</th>
      <th>Date Of Birth</th>
    </tr>
  </thead>
  <tbody>
";

// Loop through your data rows
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


$conn->close();?>

</body>
</html>

