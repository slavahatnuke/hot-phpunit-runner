<?php

namespace Hot;

class PhpunitHotRunner
{
    protected $base_dir;

    protected $phpunit_config_file;

    protected $result;

    protected $tests = [];

    protected $postfixes = ['Test', 'Tests', 'TestCase'];

    public function __construct($phpunit_config_file = null)
    {
        $this->phpunit_config_file = $phpunit_config_file;
        $this->base_dir = getcwd();
    }

    static public function handle()
    {

        $options = $_SERVER['argv'];

        if (empty($options)) {
            return;
        }

        $bin = getcwd() . '/' . $options[0];

        $request = [];

        foreach ($options as $option) {

            if (preg_match('/--(.+?)=(.+)/', $option, $a)) {
                $request[$a[1]] = $a[2];
            }

            if (preg_match('/--(.+)/', $option, $a)) {
                $request[$a[1]] = true;
            }

        }


        if (isset($request['watch'])) {
            $runner = new self();
            $runner->watch($bin, $request);
        } else {
            $config = isset($request['config']) ? $request['config'] : null;
            $config = file_exists($config) ? $config : null;
            $runner = new self($config);
            $runner->run();
        }

    }

    public function watch($bin, $request = [])
    {
        $period = isset($request['period']) ? $request['period'] : null;

        if ($request['config']) {
            $bin.= ' --config=' . $request['config'];
        }

        echo "\n";
        echo "PHPUnit HotRunner has been started";
        echo "\n";

        while (true) {
            system($bin);
            sleep($period);
        }
    }

    public function run()
    {
        $this->result = true;
        chdir($this->base_dir);

        $changes = $this->getChanges();

        $tests = [];
        $classes = [];

        foreach ($changes as $file) {

            if (preg_match('/\.php$/', $file) && file_exists($file)) {

                if (preg_match('/test/i', $file)) {
                    $tests[] = $file;
                } else {
                    $content = file_get_contents($file);

                    if (preg_match('/class\s+\w+/i', $content)) {
                        $classes[] = $file;
                    }

                }
            }
        }

        $all_files = array_merge($tests, $classes);

        if ($this->isFresh($all_files)) {
            $this->runTests($tests);
            $this->runTestsForClasses($classes);
        }


        if (count($this->tests)) {

            echo "\n";
            echo "\n";
            echo $this->result ? '[OK]' : '[FAIL]';

            if (!$this->result) {
                exit(1);
            }

            echo "\n";

        }

    }

    /**
     * @return array
     */
    protected function getChanges()
    {
        $result = [];
        $changes = [];

        exec('git status -s', $changes);
        array_walk($changes, 'trim');

        foreach ($changes as $file) {

            $a = [];
            if (preg_match('/.+?\s(.+)$/', $file, $a)) {
                $result[] = $a[1];
            }

        }

        return $result;
    }

    /**
     * @param $test
     */
    protected function runTest($test)
    {
        $real_test_path = realpath($test);

        if (isset($this->tests[$real_test_path])) {
            return 0;
        }

        $this->tests[$real_test_path] = 1;

        $cmd = "phpunit " . $test;

        if ($this->phpunit_config_file) {
            $cmd = "phpunit -c {$this->phpunit_config_file} " . $test;
        }

        echo "\n";
        echo "\n";
        echo "> " . $cmd;
        echo "\n";
        echo "\n";

        $return = null;
        system($cmd, $return);

        return $return;
    }

    /**
     * @param $tests
     */
    protected function runTests($tests)
    {
        foreach ($tests as $file) {

            $return = $this->runTest($file);

            if ($this->result && $return) {
                $this->result = false;
            }

        }
    }

    /**
     * @param $class_files
     */
    protected function runTestsForClasses($class_files)
    {
        $tests = [];

        foreach ($class_files as $class_file) {

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

            if ($class_name && $test_file = $this->findTestForClass($class_name, $ns)) {
                $tests[] = $test_file;
            }

        }

        $this->runTests($tests);
    }

    protected function findTestForClass($class_name, $ns = null)
    {

        foreach ($this->postfixes as $postfix) {

            $test_class_name = $class_name . $postfix;
            $test_class_file = $test_class_name . '.php';

            $files = [];
            exec("find . -iname '$test_class_file'", $files);

            foreach ($files as $file) {

                $find_class = $ns ? $ns . '\\' . $class_name : $class_name;
                $content = file_get_contents($file);

                //1
                if (strpos($content, $find_class) !== false) {
                    return $file;
                }

                //2
                $simple_file_path = preg_replace('/([^\w]+)test.*?([^\w]+)/i', '$1$2', $file); // remove /Test/ /Tests/
                $simple_file_path = preg_replace('/\/+/', '/', $simple_file_path); // remove ///

                $find_class_by_path = str_replace('\\', '/', $find_class);

                if (strpos($simple_file_path, $find_class_by_path) !== false) {
                    return $file;
                }

            }

        }
    }

    /**
     * @param $files
     */
    protected function isFresh($files)
    {
        $state_file = sys_get_temp_dir() . '/phpunit_test_changes_' . md5($this->base_dir . 'x' . (string)$this->phpunit_config_file);

        $previous_hash = '';

        if (file_exists($state_file)) {
            $previous_hash = file_get_contents($state_file);
        }

        // calc hash
        $hash = md5($state_file);

        foreach ($files as $file) {
            $hash = md5($hash . $file . file_get_contents($file));
        }

        file_put_contents($state_file, $hash);

        return $previous_hash !== $hash;
    }
}