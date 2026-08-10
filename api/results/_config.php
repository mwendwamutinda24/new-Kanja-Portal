<?php
/**
 * Shared subject / grade-band config for the Results API.
 *
 * ASSUMPTION: This mirrors the grade-band rules described for
 * subjects_config.php (Grade 1-3 = 4 subjects /400 with SCIE relabeled
 * as "Environmental Activities", Grade 4-6 = 8 subjects /800 with no
 * Pre-Technical, Grade 7-9 = all 9 subjects /900).
 *
 * If your actual subjects_config.php differs (different codes, different
 * band cutoffs, different labels), paste it over and these functions can
 * just delegate to it instead of duplicating the rules here.
 *
 * Subject codes match the exact column names already used in
 * UploadResults.php's manual-entry form / exam2 rows (mixed case is
 * intentional - it's what's already in the DB):
 *   MATH, ENG, KISW, SCIE, sst, ca, AGRI, re, pretec
 */

function skp_grade_band(string $grade): string
{
    $g = (int) $grade;
    if ($g >= 1 && $g <= 3) return '1-3';
    if ($g >= 4 && $g <= 6) return '4-6';
    return '7-9';
}

function skp_subjects_for_grade(string $grade): array
{
    $band = skp_grade_band($grade);

    $labels = [
        'MATH'   => 'Mathematics',
        'ENG'    => 'English',
        'KISW'   => 'Kiswahili',
        'SCIE'   => 'Science',
        'sst'    => 'Social Studies',
        'ca'     => 'C/A',
        'AGRI'   => 'Agriculture',
        're'     => 'RE',
        'pretec' => 'Pre-Technical',
    ];

    if ($band === '1-3') {
        return [
            ['code' => 'MATH', 'label' => 'Mathematics'],
            ['code' => 'ENG',  'label' => 'English'],
            ['code' => 'KISW', 'label' => 'Kiswahili'],
            ['code' => 'SCIE', 'label' => 'Environmental Activities'],
        ];
    }

    if ($band === '4-6') {
        $codes = ['MATH', 'ENG', 'KISW', 'SCIE', 'sst', 'ca', 'AGRI', 're'];
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
