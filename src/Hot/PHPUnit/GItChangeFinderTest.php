<?php


namespace Hot\PHPUnit;


class GitChangeFinderTest extends \PHPUnit_Framework_TestCase {

    protected $unique_local_file;

    protected function setUp()
    {
        $this->unique_local_file = uniqid();
    }


    protected function tearDown()
    {
        if (file_exists($this->unique_local_file)) {
            unlink($this->unique_local_file);
        }

    }


    /**
     * @test
     */
    public function shouldFindNewFile()
    {
        $finder = new GitChangeFinder(new Processor());

        file_put_contents($this->unique_local_file, 1);

        $changes = $finder->find();

        $this->assertTrue(in_array(realpath($this->unique_local_file), $changes));
    }

}
