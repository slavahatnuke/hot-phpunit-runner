<?php
namespace Hot\PHPUnit;

class RunnerTest extends \PHPUnit_Framework_TestCase {


    /**
     * @test
     */
    public function shouldGetDefaultProcessor()
    {

        $runner = new Runner(new Request([]));
        $this->assertTrue($runner->getProcessor() instanceof ProcessorInterface);
    }

    /**
     * @test
     */
    public function setProcessor()
    {
        $runner = new Runner(new Request([]));

        $processor = new Processor();
        $runner->setProcessor($processor);

        $this->assertSame($processor, $runner->getProcessor());
    }

}
