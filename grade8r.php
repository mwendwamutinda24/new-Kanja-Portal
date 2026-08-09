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
       
            
        </div>
            


    <div class="results1">

            <form method="POST" action="upload2.php">
                  <table>
                    <tr>
                      <th>Assesment no</th>
                      <th>First Name</th>
                      <th>Surname</th>
                      <th>Maths</th>
                      <th>Eng</th>
                      <th>Kisw</th>
                      <th>S/ST</th>
                      <th>Scie</th>
                      <th>C/A</th>
                      <th>AGRI</th>
                      <th>RE</th>
                      <th>PRETECH</th>
                  
                      
                      </tr>
                       <div class="se">
                        <h4 class="term">SELECT TERM BELOW</h4>
                              <select name='term' required class='select'>
                                   
                                    <Option value='Term 1'>Term 1</Option>
                                    <option value='Term 2'>Term 2</option>
                                    <option value='Term 3'>Term 3</option>
                              </select>
                       <h4 class='term'>SELECT EXAM TYPE</h4>
                              <select name='examType' required class='select'>
                                  
                                    <Option value='opener'>Opener</Option>
                                    <option value='mid-term'>Mid-Term</option>
                                    <option value='end-term'>End Term</option>
                              </select>
                       <h4 class='term'>SELECT YEAR</h4>
                              <select name='year' required class='select'>
                                   
                                    <Option value='2026'>2026</Option>
                                    <option value='2027'>2027</option>
                                    <option value='2028'>2028</option>
                                    <option value='2028'>2029</option>
                                    <option value='2028'>2030</option>
                              </select><br>
                        </div>
                    
                      <?php

                      include 'conn.php';
                      $sql = "SELECT  DISTINCT* FROM Student WHERE Grade = 8";

                     
                      $result = mysqli_query($conn, $sql);

                      while ($row = mysqli_fetch_assoc($result)) {
                        echo "
                        
                          <tr>
                             <td><input type='text' name='assesment[]' value='{$row['Assesment']}'></td>
                              <td><input type='text' name='firstName[]' value='{$row['firstName']}'></td>
                              <td><input type='text' name='surname[]' value='{$row['surname']}'></td>
                              <td><input type='text' name='MATH[]' class='input2'></td>
                              <td><input type='text' name='ENG[]'class='input2'></td>
                              <td><input type='text' name='KISW[]'class='input2'></td>
                              <td><input type='text' name='SST[]'class='input2'></td>
                              <td><input type='text' name='SCIE[]'class='input2'></td>
                              <td><input type='text' name='CA[]'class='input2'></td>
                              <td><input type='text' name='AGRI[]'class='input2'></td>
                              <td><input type='text' name='re[]'class='input2'></td>
                              <td><input type='text' name='pretec[]'class='input2'></td>
                            

                          </tr>
                        ";
                      }

                      ?>
                    
                  </table>
                  <label>INPUT THE GRADE YOU ARE APLOADING RESULTS FOR IN HERE IN <b>NUMERICS ONLY</b></label>
                   <input type='text' name='grade'class='input2'><br>
                    <h1>GRDAE 8 RESULTS UPLOAD</h1>
                     
                  <button class="btn5">Add Marks</button>
               </form>
               </div>
            

</body>
</html>