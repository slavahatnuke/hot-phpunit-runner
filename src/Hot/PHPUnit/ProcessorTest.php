<?php


namespace Hot\PHPUnit;


class ProcessorTest extends \PHPUnit_Framework_TestCase {

    /**
     * @test
     */
    public function shouldRunCommand()
    {
        $process = new Processor();
        $this->assertEquals(0, $process->run('echo "" > /dev/null'));
    }

    /**
     * @test
     */
    public function shouldReturnCode()
    {
        $process = new Processor();
        $this->assertEquals(1, $process->run('which echo-he-he'));
    }
}
