<?php
/**
 * Shared upsert helper for saving marks into `exam2`.
 *
 * ASSUMPTION: exam2 has an auto-increment `id` primary key plus
 * studentId, grade, term, examType, year, and one column per subject
 * code (MATH, ENG, KISW, SCIE, sst, ca, AGRI, re, pretec). If your
 * real exam2 schema uses different column names or a composite key
 * instead of `id`, tell me the real shape and I'll adjust this.
 *
 * Only touches the columns actually passed in $marks, so saving one
 * subject never overwrites/wipes marks for other subjects already
 * stored on the same row - this matters for the "one subject" mode.
 *
 * Returns null on success, or an error message string on failure.
 */
function skp_save_marks(
    mysqli $conn,
    string $studentId,
    string $grade,
    string $term,
    string $examType,
    string $year,
    array $marks
): ?string {
    if (count($marks) === 0) {
        return null; // nothing to save - caller decides whether that's a skip
    }

    $studentIdSafe = mysqli_real_escape_string($conn, $studentId);
    $gradeSafe     = mysqli_real_escape_string($conn, $grade);
    $termSafe      = mysqli_real_escape_string($conn, $term);
    $examTypeSafe  = mysqli_real_escape_string($conn, $examType);
    $yearSafe      = mysqli_real_escape_string($conn, $year);

    $checkQ = "SELECT id FROM exam2
               WHERE studentId = '$studentIdSafe' AND grade = '$gradeSafe'
                 AND term = '$termSafe' AND examType = '$examTypeSafe' AND year = '$yearSafe'
               LIMIT 1";
    $checkRes = mysqli_query($conn, $checkQ);
    if ($checkRes === false) {
        return mysqli_error($conn);
    }

    if (mysqli_num_rows($checkRes) > 0) {
        $row = mysqli_fetch_assoc($checkRes);
        $setParts = [];
        foreach ($marks as $code => $val) {
            $setParts[] = "`$code` = " . (int) $val;
        }
        $updateQ = "UPDATE exam2 SET " . implode(', ', $setParts) . " WHERE id = " . (int) $row['id'];
        $ok = mysqli_query($conn, $updateQ);
    } else {
        $cols = array_merge(['studentId', 'grade', 'term', 'examType', 'year'], array_keys($marks));
        $vals = array_merge(
            ["'$studentIdSafe'", "'$gradeSafe'", "'$termSafe'", "'$examTypeSafe'", "'$yearSafe'"],
            array_map(fn($v) => (int) $v, array_values($marks))
        );
        $insertQ = "INSERT INTO exam2 (" . implode(', ', array_map(fn($c) => "`$c`", $cols)) . ")
                     VALUES (" . implode(', ', $vals) . ")";
        $ok = mysqli_query($conn, $insertQ);
    }

    return $ok ? null : mysqli_error($conn);
}
