<?php

use Pest\TypeCoverage\Plugin;
use Symfony\Component\Console\Output\BufferedOutput;

test('output', function () {
    $output = new BufferedOutput();
    $plugin = new class($output) extends Plugin
    {
        public function exit(int $code): never
        {
            throw new Exception($code);
        }
    };

    expect(fn () => $plugin->handleArguments(['--type-coverage']))->toThrow(Exception::class, 0)
        ->and($output->fetch())->toContain(
            '.. 100%',
            '.. pr12 83',
            '.. pr12, pa14, pa14, rt14 0',
            '.. rt12 67',
            '.. pa12 83',
        );
});
