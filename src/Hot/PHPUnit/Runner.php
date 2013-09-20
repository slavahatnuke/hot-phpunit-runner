<?php

namespace Hot\Phpunit;

class Runner
{
    protected $base_dir;

    protected $phpunit_config_file;

    protected $phpunit_config_options;

    protected $phpunit_bin = 'phpunit';

    protected $phpunit_coverage;

    protected $result;

    protected $tests = [];

    protected $session_file;

    /**
     * @var ProcessorInterface
     */
    protected $processor;

    protected $session = [
        'changes' => [],
        'files' => [],
        'fails' => []
    ];

    protected $test_similarity = 80;

    static public function handle()
    {
        $runner = new self();
        $runner->execute(new Request($_SERVER['argv'], $_SERVER['SCRIPT_NAME']));
    }

    public function __construct()
    {
        $this->base_dir = getcwd();
        $this->session_file = sys_get_temp_dir() . '/hot_phpunit_runner_' . md5($this->base_dir);
    }

    /**
     * @param \Hot\Phpunit\ProcessorInterface $processor
     */
    public function setProcessor(ProcessorInterface $processor)
    {
        $this->processor = $processor;
    }

    /**
     * @return \Hot\Phpunit\ProcessorInterface
     */
    public function getProcessor()
    {
        if (!$this->processor) {
            $this->processor = new Processor();
        }
        return $this->processor;
    }


    /**
     * @param mixed $phpunit_config
     */
    public function setPhpunitConfigFile($phpunit_config)
    {
        if ($phpunit_config) {
            $this->phpunit_config_file = $phpunit_config;
        }
    }

    /**
     * @param mixed $phpunit_coverage
     */
    public function setPhpunitCoverage($phpunit_coverage)
    {
        if ($phpunit_coverage) {
            $this->phpunit_coverage = $phpunit_coverage;
        }
    }


    /**
     * @param string $phpunit_bin
     */
    public function setPhpunitBin($phpunit_bin)
    {
        if ($phpunit_bin) {
            $this->phpunit_bin = $phpunit_bin;
        }
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

    /**
     * @param mixed $phpunit_config_options
     */
    public function setPhpunitConfigOptions($phpunit_config_options)
    {
        if ($phpunit_config_options) {
            $this->phpunit_config_options = $phpunit_config_options;
        }
    }


    public function clean()
    {
        if (file_exists($this->session_file)) {
            unlink($this->session_file);
        }
    }


    public function watch(Request $request)
    {
        $bin = $request->getBin();

        $options = ['config', 'phpunit-bin', 'test-similarity', 'options', 'coverage'];

        foreach ($options as $option) {
            if ($request->has($option)) {
                $bin .= " --'{$option}'=" . '"' . $request->get($option) . '"';
            }
        }

        echo "\n";
        echo "Hot\\PHPUnit\\Runner has been started";
        echo "\n";

        $period = $request->has('period') ? $request->get('period') : 2;

        while (true) {
            $this->getProcessor()->run($bin);
            sleep($period);
        }
    }

    public function run()
    {
        $this->loadSession();

        $prev_changes = array_keys($this->session['changes']);

        $this->session['changes'] = [];

        $this->result = true;
        chdir($this->base_dir);

        $changes = $this->getChanges();
        $changes = array_unique(array_merge($prev_changes, $changes));

        foreach ($changes as $file) {
            if ($this->isPhp($file) && file_exists($file)) {
                $this->runFile($file);
            }
        }

        $this->saveSession();

        $this->report();
    }

    protected function loadSession()
    {

        if (!file_exists($this->session_file)) {
            $this->saveSession();
        }

        $this->session = array_merge($this->session, json_decode(file_get_contents($this->session_file), 1));
    }

    protected function saveSession()
    {
        file_put_contents($this->session_file, json_encode($this->session));
    }

    /**
     * @param $request
     * @param $runner
     */
    public function execute(Request $request)
    {
        if ($request->has('clean')) {
            $this->clean();
        } else if ($request->has('watch')) {
            $this->watch($request);
        } else {

            $this->setPhpunitConfigFile($request->get('config'));
            $this->setPhpunitBin($request->get('phpunit-bin'));
            $this->setTestSimilarity($request->get('test-similarity'));
            $this->setPhpunitConfigOptions($request->get('options'));
            $this->setPhpunitCoverage($request->get('coverage'));

            $this->run();
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

        $file = realpath($file);

        $file_hash = md5(file_get_contents($file));

        $this->session['changes'][$file] = $file_hash;

        $prev_file_hash = isset($this->session['files'][$file]) ? $this->session['files'][$file] : null;

        if ($file_hash == $prev_file_hash) {
            return;
        }

        $this->session['files'][$file] = $file_hash;

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
     * @param $test_file
     */
    protected function runTest($test_file)
    {
        $test_file = realpath($test_file);

        if (isset($this->tests[$test_file])) {
            return 0;
        }

        $test_file_hash = md5(file_get_contents($test_file));
        $this->tests[$test_file] = $test_file_hash;

        $cmd = $this->phpunit_bin;

        if ($this->phpunit_config_options) {
            $cmd .= ' ' . $this->phpunit_config_options;
        }

        if ($this->phpunit_coverage) {
            $cmd .= ' --coverage-clover ' . $this->phpunit_coverage;
        }

        if ($this->phpunit_config_file) {
            $cmd .= " -c {$this->phpunit_config_file}";
        }

        $cmd .= ' ' . $test_file;

        echo "\n";
        echo "\n";
        echo "> " . $cmd;
        echo "\n";
        echo "\n";

        $return = $this->getProcessor()->run($cmd);

        if ($return) {
            $this->session['fails'][$test_file] = $test_file_hash;
        } else if (isset($this->session['fails'][$test_file])) {
            unset($this->session['fails'][$test_file]);
        }

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
        $result = 0;

        foreach ($tests as $file) {
            if ($this->runTest($file)) {
                $result = 1;
            }
        }

        return $result;
    }

    protected function findTestsForClass($class_name, $ns = null)
    {
        $result = [];

        $files = [];
        exec("find . -type f -iname '$class_name*'", $files);

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

        return $this->runTests($tests);
    }

    protected function report()
    {
        if (count($this->tests)) {

            echo "\n";
            echo "\n";
            echo $this->result ? '[OK]' : '[FAIL]';
            echo "\n";

            if (count($this->session['fails'])) {

                echo "\n";
                echo "[NOTICE] You have fail(s):";

                foreach (array_keys($this->session['fails']) as $name) {
                    echo "\n";
                    echo $name;
                }

                echo "\n";


            }


            if (!$this->result) {
                exit(1);
            }


        }
    }

    /**
     * @param $file
     * @return int
     */
    protected function isPhp($file)
    {
        return preg_match('/\.php$/', $file);
    }

}