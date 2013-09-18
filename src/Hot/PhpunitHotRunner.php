<?php

namespace Hot;

class PhpunitHotRunner
{
    protected $base_dir;

    protected $phpunit_config_file;

    protected $result;

    protected $tests = [];

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
            $bin .= ' --config=' . $request['config'];
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

        $files = [];

        foreach ($this->getChanges() as $file) {
            if (preg_match('/\.php$/', $file) && file_exists($file)) {
                $files[] = $file;
            }
        }

        if ($this->isFresh($files)) {
            $this->runFiles($files);
        }

        if (count($this->tests)) {

            echo "\n";
            echo "\n";
            echo $this->result ? '[OK]' : '[FAIL]';
            echo "\n";

            if (!$this->result) {
                exit(1);
            }


        }

    }

    protected function runFiles($files)
    {
        foreach ($files as $file) {
            $this->runFile($file);
        }
    }

    protected function runFile($file)
    {
        if ($this->isTest($file)) {
            $this->runTest($file);
        } else if ($this->isClass($file)) {
            $this->runTestsForClass($file);
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
        if (isset($this->tests[$test])) {
            return 0;
        }

        $this->tests[$test] = md5(file_get_contents($test));

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

        if ($this->result && $return) {
            $this->result = false;
        }

        return $return;
    }

    /**
     * @param $tests
     */
    protected function runTests($tests)
    {
        foreach ($tests as $file) {
            $this->runTest($file);
        }
    }

    /**
     * @param $class_files
     */
    protected function runTestsForClasses($class_files)
    {

        foreach ($class_files as $class_file) {
            $this->runTestsForClass($class_file, $tests);
        }
    }

    protected function findTestsForClass($class_name, $ns = null)
    {
        $result = [];

        $files = [];
        exec("find . -iname '$class_name*'", $files);

        $class = $ns ? $ns . '\\' . $class_name : $class_name;
        $a_class = explode('\\', $class);

        foreach ($files as $file) {

            if ($this->isTest($file)) {

                if (strpos($file, $class_name) !== false) {

                    $n = 0;

                    foreach ($a_class as $name) {
                        if (strpos($file, $name) !== false) {
                            $n++;
                        }
                    }

                    if ($n > count($a_class)/2) {
                        $result[] = $file;
                    }

                }
            }

        }

        return array_unique($result);
    }

    /**
     * @param $files
     */
    protected function isFresh($files)
    {
        return true;
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

    /**
     * @param $file
     * @return int
     */
    protected function isTest($file)
    {
        return preg_match('/test/i', $file);
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
     * @param $class_file
     * @param $tests
     * @return array
     */
    protected function runTestsForClass($class_file)
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

        $this->runTests($tests);
    }
}