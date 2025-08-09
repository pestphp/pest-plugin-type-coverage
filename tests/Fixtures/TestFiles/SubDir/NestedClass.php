<?php

namespace Tests\Fixtures\TestFiles\SubDir;

class NestedClass
{
    private $value;

    public function setValue($value)
    {
        $this->value = $value;
    }

    public function getValue()
    {
        return $this->value;
    }
}
