<?php
namespace Hot\PHPUnit;

use Mockery as M;

class RunnerTest extends \PHPUnit_Framework_TestCase {
    /**
     * @test
     */
    public function shouldGetDefaultProcessor()
    {
        $runner = new Runner();
        $this->assertTrue($runner->getProcessor() instanceof ProcessorInterface);
    }

    /**
     * @test
     */
    public function setProcessor()
    {
        $runner = new Runner();

        $processor = M::mock('Hot\PHPUnit\ProcessorInterface');
        $runner->setProcessor($processor);

        $this->assertSame($processor, $runner->getProcessor());
    }

}
