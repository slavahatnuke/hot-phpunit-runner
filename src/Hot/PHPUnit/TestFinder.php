<?php
namespace Hot\PHPUnit;


class TestFinder
{

    protected $processor;

    protected $test_similarity = 80;

    public function __construct(ProcessorInterface $processor)
    {
        $this->processor = $processor;
    }

    /**
     * @param int $test_similarity
     */
    public function setTestSimilarity($test_similarity)
    {
        $test_similarity = (int)$test_similarity;

        if ($test_similarity) {
            $this->test_similarity = $test_similarity;
        }
    }

    public function findTests($files)
    {
        $result = [];

        foreach ((array)$files as $file) {
            $result = array_merge($result, $this->findTestsForFile($file));
        }

        return array_unique($result);
    }

    /**
     * @param $file
     * @return int
     */
    protected function isTest($file)
    {
        $file = realpath($file);

        if( $file && preg_match('/\w+test/i', $file) && $this->isClass($file))
        {
            $content = file_get_contents($file);
            return !preg_match('/abstract\s+class\s+/i', $content);
        }

        return false;
    }

    /**
     * @param $file
     * @param $classes
     * @return array
     */
    protected function isClass($file)
    {
        $content = file_get_contents($file);
        return preg_match('/class\s+\w+/i', $content);
    }


    /**
     * @param $file
     * @return int
     */
    protected function isPhp($file)
    {
        return preg_match('/\.php$/', $file);
    }

    /**
     * @param $class_file
     * @param $tests
     * @return array
     */
    protected function findTestsForClassFile($class_file)
    {
        $tests = [];

        $content = file_get_contents($class_file);

        $ns = null;

        $a = [];
        if (preg_match('/namespace\s+([^\s]+);/i', $content, $a)) {
            $ns = $a[1];
        }

        $class_name = null;

        if (preg_match('/class\s+([^\s]+)/i', $content, $a)) {
            $class_name = $a[1];
        }

        if ($class_name && $test_files = $this->findTestsForClass($class_name, $ns)) {
            $tests = array_merge($tests, $test_files);
        }

        return $tests;
    }


    protected function findTestsForClass($class_name, $ns = null)
    {
        $result = [];

        $files = $this->processor->execute("find . -type f -iname '$class_name*.php'");
        $files = $files ? $files : [];

        $class = $ns ? $ns . '\\' . $class_name : $class_name;
        $a_class = explode('\\', $class);

        foreach ($files as $file) {

            if ($this->isTest($file) && $this->isPhp($file)) {

                if (strpos($file, $class_name) !== false) {

                    $n = 0;

                    foreach ($a_class as $name) {
                        if (strpos($file, $name) !== false) {
                            $n++;
                        }
                    }

                    if ($n / count($a_class) >= ($this->test_similarity / 100)) {
                        $result[] = $file;
                    }

                }
            }

        }

        return array_unique($result);
    }

    /**
     * @param $file
     * @return array
     */
    protected function findTestsForFile($file)
    {
        $result = [];

        if ($this->isPhp($file)) {
            if ($this->isTest($file)) {
                $result[] = $file;
            } else if ($this->isClass($file)) {
                $result = array_merge($result, $this->findTestsForClassFile($file));
            }
        }

        foreach ($result as $i => $file) {
            $result[$i] = realpath($file);
        }

        return array_unique($result);
    }

}