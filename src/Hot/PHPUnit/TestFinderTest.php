<?php


namespace Hot\PHPUnit;

class TestFinderTest extends \PHPUnit_Framework_TestCase {


    protected $class_name;
    protected $file;
    protected $file_test;

    protected function setUp()
    {
        $this->class_name = 'cls_' . uniqid();

        $this->file = __DIR__ . '/' . $this->class_name . '.php';
        $this->file_test = __DIR__ . '/' . $this->class_name . 'Test.php';

    }

    protected function tearDown()
    {
        if(file_exists($this->file))
        {
            unlink($this->file);
        }

        if(file_exists($this->file_test))
        {
            unlink($this->file_test);
        }
    }


    /**
     * @test
     */
    public function shouldFindTest()
    {

        $file = $this->file_test;

        file_put_contents($file, 'class w1');

        $finder = $this->newFinder();
        $tests = $finder->findTests($file);

        $this->assertTrue(in_array($file, $tests));
    }

    /**
     * @test
     */
    public function shouldFindClassTests()
    {
        $class_name = $this->class_name;

        $file =  $this->file;
        $file_test =  $this->file_test;

        file_put_contents($file, "namespace Hot\PHPUnit; \n class {$class_name} ");

        file_put_contents($file_test, 'class someTest');

        $finder = $this->newFinder();
        $finder->setTestSimilarity(20);
        $tests = $finder->findTests($file);

        $this->assertEquals(1, count($tests));
        $this->assertEquals($file_test, $tests[0]);
    }


    /**
     * @test
     */
    public function shouldUseTestSimilarity()
    {
        $class_name = $this->class_name;

        $file =  $this->file;
        $file_test =  $this->file_test;

        file_put_contents($file, "namespace Hot\PHPUnit; \n class {$class_name} ");

        file_put_contents($file_test, 'class test');

        $finder = $this->newFinder();
        $finder->setTestSimilarity(200);

        $tests = $finder->findTests($file);

        $this->assertEquals(0, count($tests));

        $finder->setTestSimilarity(10);

        $tests = $finder->findTests($file);
        $this->assertEquals(1, count($tests));
    }

    /**
     * @return TestFinder
     */
    protected function newFinder()
    {
        return new TestFinder(new Processor());
    }
}

