<?php


namespace Hot\PHPUnit;


class PHPUnitTest extends \PHPUnit_Framework_TestCase {
    
    protected $processor;
    
    protected $coverage_file_finder;

    protected function setUp()
    {
        $this->processor = $this->getMock('Hot\PHPUnit\Processor', ['run']);
        $this->coverage_file_finder = $this->getMock('Hot\PHPUnit\CoverageFileFinder', ['find'], [], '', false);
    }

    /**
     * @test
     */
    public function shouldConstruct()
    {
        $options = [];
        $this->newPhpunit($options);
    }

    /**
     * @test
     */
    public function shouldRunTest()
    {
        $phpunit = $this->newPhpunit();

        $this->processor->expects($this->once())
            ->method('run')
            ->with($this->equalTo('phpunit xTest.php'));

        $phpunit->run('xTest.php');
    }

    /**
     * @test
     */
    public function shouldRunTestWithConfig()
    {

        $phpunit = $this->newPhpunit(['config' => 'custom.config.xml']);
        $this->assertSame('phpunit -c custom.config.xml xTest.php', $phpunit->generateBin('xTest.php'));
    }

    /**
     * @test
     */
    public function shouldRunTestWithCustomPhpunitOptions()
    {
        $phpunit = $this->newPhpunit(['phpunit-options' => '--tap']);
        $this->assertSame('phpunit --tap xTest.php', $phpunit->generateBin('xTest.php'));
    }

    /**
     * @test
     */
    public function shouldRunTestWithCoverage()
    {
        $phpunit = $this->newPhpunit(['coverage' => 'some.coverage.file.xml']);
        $this->assertSame('phpunit --coverage-clover some.coverage.file.xml xTest.php', $phpunit->generateBin('xTest.php'));
    }

    /**
     * @test
     */
    public function shouldRunTestWithCustomPhpunitBin()
    {
        $phpunit = $this->newPhpunit(['phpunit-bin' => 'bin/phpunit']);
        $this->assertSame('bin/phpunit xTest.php', $phpunit->generateBin('xTest.php'));
    }

    /**
     * @test
     */
    public function isCoverageMode()
    {
        $phpunit = $this->newPhpunit(['config' => __FILE__, 'coverage' => 1]);
        $this->assertTrue($phpunit->isCoverageMode());
    }

    /**
     * @test
     */
    public function isNotCoverageMode()
    {
        $phpunit = $this->newPhpunit(['config' => __FILE__]);
        $this->assertFalse($phpunit->isCoverageMode());
    }

    /**
     * @test
     */
    public function workWithNewConfigFile()
    {
        $this->coverage_file_finder->expects($this->once())
            ->method('find')
            ->will($this->returnValue(['file_xxx_file']));


        $phpunit = $this->newPhpunit(['config' => __DIR__ . '/Fixtures/PHPUnit/phpunit.xml', 'coverage' => 1]);


        $new_config = $phpunit->beforeHandle();

        $this->assertTrue($phpunit->isCoverageMode());
        
        $this->assertFileExists($new_config);
        $this->assertContains('<filter><whitelist><file>file_xxx_file</file></whitelist></filter>', file_get_contents($new_config));

        $phpunit->afterHandle();
        $this->assertFileNotExists($new_config);

    }


    /**
     * @param array $options
     * @return PHPUnit
     */
    protected function newPhpunit($options = [])
    {
        return new PHPUnit(new Map($options), $this->processor, $this->coverage_file_finder);
    }


}
