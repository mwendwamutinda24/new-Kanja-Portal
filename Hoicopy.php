<?php
session_start();
if (!isset($_SESSION['loggedin'])) {
    header("Location: login.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Kanja Home</title>
  <link rel="stylesheet" href="index.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

  <!-- Header with logo -->
  <div class="header">
    <div class="header1">
 
      <div>
        <h1>Stephen Kanja  School</h1>
      </div>
    </div>
   
  </div>

  <div class="home">
    <!-- Sidebar Dashboard -->
    <div class="dashboard">
      <h2><i class="fa-solid fa-gauge"></i> Dashboard</h2>
      <a href="Hoi.php"><i class="fa-solid fa-house"></i> Home</a>
      <a href="Students.php"><i class="fa-solid fa-user-graduate"></i> Students</a>
      <a href="progress.php"><i class="fa-solid fa-chart-line"></i> Progress Records</a>
      <a href="RegisterStudent.php"><i class="fa-solid fa-user-plus"></i> Register Learners</a>
        <a href="RegisterTeacher.php"><i class="fa-solid fa-user-plus"></i> Register Teachers</a>
      <a href="ViewResults.php"><i class="fa-solid fa-chart-pie"></i> Results</a>
        <a href="UploadResults.php"><i class="fa-solid fa-chart-pie"></i>Upload Results</a>
      <a href="track.php"><i class="fa-solid fa-bullseye"></i> Track Performance</a>
              <a href="Fee.php"><i class="fa-solid fa-coins"></i> Finaces</a>
    </div>
      
     

   <div class="hoi-dahsboard">
           <h1>School Dashboard</h1>
           <div class="hoi-dahsboard1">
               <div class="hoi-dash">
               
                   <h2><?php
include('conn.php');

$sql = "SELECT COUNT(*) AS total FROM Student";
$result = mysqli_query($conn, $sql);

if ($row = mysqli_fetch_assoc($result)) {
    echo "<div class='value'>" . $row['total'] . "</div>";

}
?>
</h2>
                       <h3>No of Learners</h3>
                   <p>Active learners in the instution</p>
               </div>
       
                <div class="hoi-dash">
                    <?php
                    include 'conn.php';
                    $sql="SELECT DISTINCT COUNT(*) as total FROM Teachers";
                    $result=mysqli_query($conn,$sql);
                    if($row=mysqli_fetch_assoc($result)){
                        echo "<div class='value'>" . $row['total'] . "</div>";
                    }
                    ?><p>Available Number of Teachers</p>
                      <h3>No of Teachers</h3>
                   
               </div>
                <div class="hoi-dash">
                   
                    <h2 class="value">9</h2>
                     <h3>Classes </h3>
                    <p>Total Number of enrolled classes</p>
               </div>

               
           </div>
           
        </div>
    </div>
    
 
   <div class="teachers">
  <table>
    <thead>
   
      <tr class="staff-banner">
        <th colspan="7">Our Esteemed Teaching Staff</th>
      </tr>

 
      <tr>
        <th>Teacher's Name</th>
        <th>Email</th>
        <th>Phone Number</th>
        <th>TSC No</th>
        <th>Role</th>
        <th>Class Teacher</th>
        <th>Subject</th>
      </tr>
    </thead>
    <tbody>
      <?php
      include 'conn.php';
      $sql = "SELECT * FROM Teachers";
      $result = mysqli_query($conn, $sql);

      while ($row = mysqli_fetch_assoc($result)) {
          echo "<tr>
                  <td>{$row['name']}</td>
                  <td>{$row['email']}</td>
                  <td>{$row['phoneNo']}</td>
                  <td>{$row['tscNo']}</td>
                  <td>{$row['role']}</td>
                  <td>{$row['classTeacher']}</td>
                  <td>{$row['subject']}</td>
                </tr>";
      }
      ?>
    </tbody>
  </table>
</div>

    </div>
  
  <!-- Footer -->
  <div class="copy">
    <p>&copy; Designed by Kelvin Mutinda 2026. All rights reserved.</p>
  </div>

</body>
</html>
