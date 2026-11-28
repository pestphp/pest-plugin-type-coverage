<?php

use Pest\TypeCoverage\Logging\JsonLogger;

it('does not report a total of 100 while a file is below 100', function () {
    $path = sys_get_temp_dir().'/type-coverage-'.uniqid().'.json';
    $logs = [...array_fill(0, 9999, ['percentage' => 100.0]), ['percentage' => 75.0]];

    (new JsonLogger($path, 0.0, $logs))->output();

    $json = (string) file_get_contents($path);
    unlink($path);

    expect(json_decode($json, true)['total'])->toBe(99.99);
});
