<?php
/*
 * fetch_students.php
 * ==================
 * Called via XHR from Fee.php when a grade is selected.
 * Returns HTML <tr> rows for each student in that grade.
 *
 * GET param: grade (integer 1–9)
 *
 * Place this file in the SAME folder as Fee.php and conn.php.
 */

/* Suppress PHP warnings leaking into the HTML response */
error_reporting(0);
ini_set('display_errors', '0');

include 'conn.php';

/* ── Validate input ── */
if (!isset($_GET['grade']) || !is_numeric($_GET['grade'])) {
    http_response_code(400);
    echo '<tr><td colspan="8" style="text-align:center;padding:2rem;color:#888;">
            Invalid grade parameter.
          </td></tr>';
    exit;
}

$grade = intval($_GET['grade']);

if ($grade < 1 || $grade > 9) {
    http_response_code(400);
    echo '<tr><td colspan="8" style="text-align:center;padding:2rem;color:#888;">
            Grade must be between 1 and 9.
          </td></tr>';
    exit;
}

/* ── Query ── */
$stmt = $conn->prepare(
    "SELECT Assesment, firstName, surname
     FROM Student
     WHERE Grade = ? AND role = 'user'
     ORDER BY surname, firstName"
);

if (!$stmt) {
    http_response_code(500);
    echo '<tr><td colspan="8" style="text-align:center;padding:2rem;color:#c00;">
            Database error: ' . htmlspecialchars($conn->error) . '
          </td></tr>';
    exit;
}

$stmt->bind_param('i', $grade);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo '<tr><td colspan="8">
            <div style="text-align:center;padding:3rem 2rem;">
              <div style="width:56px;height:56px;background:#111;border-radius:14px;display:flex;align-items:center;justify-content:center;margin:0 auto 1rem;">
                <i class="fa-solid fa-user-slash" style="color:#f0c040;font-size:20px;"></i>
              </div>
              <p style="font-size:14px;color:#888;">No students found in Grade ' . $grade . '.</p>
            </div>
          </td></tr>';
    $stmt->close();
    $conn->close();
    exit;
}

/* ── Output rows ── */
while ($row = $result->fetch_assoc()) {
    $assess    = htmlspecialchars($row['Assesment'] ?? '');
    $firstName = htmlspecialchars($row['firstName'] ?? '');
    $surname   = htmlspecialchars($row['surname']   ?? '');

    echo "<tr>
  <td><span class='cell-assess'>{$assess}</span></td>
  <td><span class='cell-name'>{$firstName}</span></td>
  <td><span class='cell-name'>{$surname}</span></td>
  <td><input type='number' name='Fee[]'           placeholder='0' min='0' step='any'></td>
  <td><input type='number' name='AssessmentFee[]' placeholder='0' min='0' step='any'></td>
  <td><input type='number' name='Activity[]'      placeholder='0' min='0' step='any'></td>
  <td><input type='number' name='Other[]'         placeholder='0' min='0' step='any'></td>

  <input type='hidden' name='firstName[]'  value='{$firstName}'>
  <input type='hidden' name='surname[]'    value='{$surname}'>
  <input type='hidden' name='assessment[]' value='{$assess}'>
</tr>";
}

$stmt->close();
$conn->close();
exit;