<?php


namespace Hot\PHPUnit;


class ProcessorTest extends \PHPUnit_Framework_TestCase {

    /**
     * @test
     */
    public function shouldRunValidCommand()
    {
        $process = new Processor();
        $this->assertEquals(true, $process->run('echo "" > /dev/null'));
    }

    /**
     * @test
     */
    public function shouldRunCommandWithErrorReturnCode()
    {
        $process = new Processor();
        $this->assertEquals(false, $process->run('which echo-he-he'));
    }

    /**
     * @test
     */
    public function shouldExecuteValidCommand()
    {
        $process = new Processor();
        $this->assertEquals(['value'], $process->execute('echo "value"'));
    }

    /**
     * @test
     */
    public function shouldExecuteCommandWithError()
    {
        $process = new Processor();
        $this->assertEquals(null, $process->execute('which echo-he-he'));
    }

}
