<?php

namespace Tests\Fixtures\TestFiles;

class MissingTypes
{
    public function processData($data)
    {
        return $data;
    }

    public function calculate($a, $b)
    {
        return $a + $b;
    }
}
