<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';

$tests = [
    ['905001234', true],
    ['90500999', true],
    ['90500', false],
    ['12345999', false],
    ['90500ABC', false],
    ['90500 123', false],
    [' 905001234', false],
    ['905001234 ', false],
    ['90500-1234', false],
];

$failed = 0;
foreach ($tests as [$studentNumber, $expected]) {
    $actual = is_valid_student_number($studentNumber);
    $passed = ($actual === $expected);
    printf("%s: %s (expected %s, got %s)\n", $passed ? 'PASS' : 'FAIL', $studentNumber, $expected ? 'valid' : 'invalid', $actual ? 'valid' : 'invalid');
    if (!$passed) {
        $failed++;
    }
}

printf("\n%d test(s) failed.\n", $failed);
exit($failed === 0 ? 0 : 1);
