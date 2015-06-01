<?php

namespace Port\Steps\Tests\Step;

use Port\Steps\Step\MappingStep;

class MappingStepTest extends \PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        $this->filter = new MappingStep();
    }

    public function testProcess()
    {
        $this->filter->map('[foo]', '[bar]');

        $data = [
            'foo' => '1',
        ];

        $this->filter->process($data);

        $this->assertEquals(['bar' => '1'], $data);
    }
}
