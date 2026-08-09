
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kanja Home</title>
    <link rel="stylesheet" href="index.css">
    <head>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
    <div class="header">
        <div class="header1">
            <h1>Stephen Kanja Primary And Junior school</h1>
            <h3>Aim Higher</h3>
        </div>
        
    </div>
    <div class="home">
         <div class="dashboard">
            <h2>Dashboard</h2>
            <a href="Students.php">Students</a><br>
            <a href="Admin.php">Admin</a><br>
            <a href="HOIAccess.php">HOI Panel</a><br>
            <a href="ViewResults.php">Results</a><br>
            <a href="RegisterStudent.php">Register Student</a><br>
            <a href="Lesson.php">Lesson Management</a><br>
            <a href="UploadResults.php">Upload Results</a>
        </div>
            


    <div class="results">

            <form method="POST" action="FeeM.php">
                  <table>
                    <tr>
                      <th>Assesment no</th>
                      <th>First Name</th>
                      <th>Surname</th>
                      <th>PTA</th>
                      <th>ASSESMENTS</th>
                      <th>PROJECTS</th>
                      <th>OTHER</th>
                      
                      </tr>
                   <div class="se">
                     <h2 class="term">SELECT TERM BELOW</h2>
                              <select name='term' required class='select'>
                                    <Option value='Term 1'>Term 1</Option>
                                    <option value='Term 2'>Term 2</option>
                                    <option value='Term 3'>Term 3</option>
                              </select><br>
                              <h2 class="term">Choose Year</h2>
                              <select name='year' required class='select'>
                                    <Option value='2026'>2026</Option>
                                    <option value='2027'>2027</option>
                                    <option value='2028'>2028</option>
                                     <option value='2029'>2029</option>
                                      <option value='2030'>2030</option>
                              </select><br>
                          </div>
                      <?php

                      include 'conn.php';
                      $sql = "SELECT * FROM Student WHERE Grade = 3";

                     
                      $result = mysqli_query($conn, $sql);

                      while ($row = mysqli_fetch_assoc($result)) {
                        echo "
                          <tr>
                             <td><input type='text' name='assesment[]' value='{$row['Assement']}'></td>
                              <td><input type='text' name='firstName[]' value='{$row['firstName']}'></td>
                              <td><input type='text' name='surname[]' value='{$row['surname']}'></td>
                              <td><input type='text' name='PTA[]'class='input2'></td>
                              <td><input type='text' name='Exam[]'class='input2'></td>
                              <td><input type='text' name='Projects[]'class='input2'></td>
                              <td><input type='text' name='other[]'class='input2'></td>
                            

                          </tr>
                        ";
                      }

                      ?>
                    
                  </table>
                    <label>INPUT THE GRADE YOU ARE APLOADING FEE FOR IN HERE IN NUMERICS ONLY</label>
                   <input type='text' name='grade'class='input2' required><br>
                   <h1>GRDAE 3 FEE UPLOAD</h1>
                    
                  <button class="btn4">PayFees</button>
               </form>
               </div>
            

</body>
</html>