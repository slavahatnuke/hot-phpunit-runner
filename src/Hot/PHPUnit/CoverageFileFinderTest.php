<?php


namespace Hot\PHPUnit;


class CoverageFileFinderTest extends \PHPUnit_Framework_TestCase {

    protected $test_finder;

    protected $change_provider;

    protected function setUp()
    {
        $this->change_provider = $this->getMock('Hot\PHPUnit\ChangeProvider', ['getChanges'], [], '', false);
        $this->test_finder = $this->getMock('Hot\PHPUnit\TestFinder', ['findTests'], [], '', false);
    }

    /**
     * @test
     */
    public function find()
    {
        $changes = ['file1.php', 'file1Test.php'];

        $this->change_provider->expects($this->once())
            ->method('getChanges')
            ->will($this->returnValue($changes));

        $this->test_finder->expects($this->once())
            ->method('findTests')
            ->with($this->equalTo($changes))
            ->will($this->returnValue(['file1Test.php', 'file2.php']));

        $finder = new CoverageFileFinder($this->change_provider, $this->test_finder);
        $this->assertEquals(['file1.php', 'file1Test.php', 'file2.php'], $finder->find());
    }

}
