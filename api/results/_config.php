<?php
function skp_band_info_for_grade(int $score, int $gradeInt): array
{
    $isLower = $gradeInt >= 1 && $gradeInt <= 6; // 4-level bands for Grade 1-6, 8-level for 7-9
    if ($isLower) {
        if ($score >= 75) return ['code' => 'E.E', 'label' => 'Exceeding Expectation'];
        if ($score >= 50) return ['code' => 'M.E', 'label' => 'Meeting Expectation'];
        if ($score >= 26) return ['code' => 'A.E', 'label' => 'Approaching Expectation'];
        return ['code' => 'B.E', 'label' => 'Below Expectation'];
    }
    if ($score >= 90) return ['code' => 'EE2', 'label' => 'Exceeding Expectation 2'];
    if ($score >= 75) return ['code' => 'EE1', 'label' => 'Exceeding Expectation 1'];
    if ($score >= 63) return ['code' => 'ME2', 'label' => 'Meeting Expectation 2'];
    if ($score >= 50) return ['code' => 'ME1', 'label' => 'Meeting Expectation 1'];
    if ($score >= 38) return ['code' => 'AE2', 'label' => 'Approaching Expectation 2'];
    if ($score >= 26) return ['code' => 'AE1', 'label' => 'Approaching Expectation 1'];
    if ($score >= 13) return ['code' => 'BE2', 'label' => 'Below Expectation 2'];
    return ['code' => 'BE1', 'label' => 'Below Expectation 1'];
}
function skp_grade_band(string $grade): string
{
    $g = (int) $grade;
    if ($g >= 1 && $g <= 3) return '1-3';
    if ($g >= 4 && $g <= 6) return '4-6';
    return '7-9';
}
function skp_subjects_for_grade(string $grade): array
{
    // Codes must match exam2's actual (lowercase) column names exactly —
    // see the exam2 schema: math, eng, kisw, sst, scie, ca, agri, re, pretec.
    $band = skp_grade_band($grade);
    $labels = [
        'math'   => 'Mathematics',
        'eng'    => 'English',
        'kisw'   => 'Kiswahili',
        'scie'   => 'Science',
        'sst'    => 'Social Studies',
        'ca'     => 'C/A',
        'agri'   => 'Agriculture',
        're'     => 'RE',
        'pretec' => 'Pre-Technical',
    ];
    if ($band === '1-3') {
        return [
            ['code' => 'math', 'label' => 'Mathematics'],
            ['code' => 'eng',  'label' => 'English'],
            ['code' => 'kisw', 'label' => 'Kiswahili'],
            ['code' => 'scie', 'label' => 'Environmental Activities'],
        ];
    }
    if ($band === '4-6') {
        $codes = ['math', 'eng', 'kisw', 'scie', 'sst', 'ca', 'agri', 're'];
    } else {
        $codes = array_keys($labels);
    }
    $out = [];
    foreach ($codes as $c) {
        $out[] = ['code' => $c, 'label' => $labels[$c]];
    }
    return $out;
}
function skp_max_total(string $grade): int
{
    $band = skp_grade_band($grade);
    if ($band === '1-3') return 400;
    if ($band === '4-6') return 800;
    return 900;
}
