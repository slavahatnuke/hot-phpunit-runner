<?php
namespace Hot\PHPUnit;

class Runner
{
    protected $base_dir;

    protected $on_fail;

    protected $on_pass;

    protected $result = true;

    protected $executed_tests = [];

    protected $base_tmp;

    protected $notify;

    /**
     * @var ChangeProvider
     */
    protected $change_provider;

    /**
     * @var TestFinder
     */
    protected $test_finder;


    /**
     * @var PHPUnit
     */
    protected $phpunit;

    /**
     * @var Session
     */
    protected $fails_session;

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var ProcessorInterface
     */
    protected $processor;


    static public function handle()
    {
        $runner = new self(new Request($_SERVER['argv'], $_SERVER['SCRIPT_NAME']));
        $runner->execute();

        return $runner;
    }

    public function __construct(Request $request)
    {
        $this->request = $request;
        $this->base_dir = getcwd();
        $this->base_tmp = sys_get_temp_dir() . '/hot_phpunit_runner_' . md5($this->base_dir);
    }

    /**
     * @param $request
     * @param $runner
     */
    public function execute()
    {
        if ($this->request->has('clean')) {
            $this->clean();
        } else if ($this->request->has('watch')) {
            $this->watch();
        } else {

            $this->setTestSimilarity($this->request->get('test-similarity'));

            $this->setOnFail($this->request->get('on-fail'));
            $this->setOnPass($this->request->get('on-pass'));
            $this->setNotify($this->request->get('notify'));

            $this->run();
        }
    }

    public function clean()
    {
        $this->getChangeProvider()->reset();
        $this->getFailsSession()->remove();
    }

    protected function save()
    {
        $this->getChangeProvider()->commit();
        $this->getFailsSession()->save();
    }


    public function watch()
    {
        $period = $this->request->has('period') ? $this->request->get('period') : 2;

        $this->request->delete('watch');
        $this->request->delete('period');

        $bin = $this->request->generateBin($this->request);

        echo "\n";
        echo "Hot\\PHPUnit\\Runner has been started";
        echo "\n";

        while (true) {
            $this->getProcessor()->run($bin);
            sleep($period);
        }
    }

    public function run()
    {

        $this->beforeRunFiles();

        foreach ($this->getChangeProvider()->getChanges() as $file) {
            $this->runFile($file);
        }

        $this->afterRunFiles();

        if ($this->hasExecutedTests() && !$this->result) {
            exit(1);
        }

    }

    protected function beforeRunFiles()
    {
        if ($this->getPhpunit()->isCoverageMode()) {
            $this->getPhpunit()->generateNewConfig($this->getPhpunitFilterFiles());
        }
    }

    protected function afterRunFiles()
    {
        if ($this->getPhpunit()->isCoverageMode()) {
            $this->getPhpunit()->removeNewConfig();
        }

        $this->save();

        $this->report();

        $this->handleCallbacks();

    }

    protected function runFile($file)
    {
        $this->runTests($this->getTestFinder()->findTests($file));
    }

    /**
     * @param $test_file
     */
    protected function runTest($test_file)
    {
        if($this->isExecutedTest($test_file))
            return;

        echo "\n";
        echo "\n";
        echo "> " . $this->getPhpunit()->generateBin($test_file);
        echo "\n";
        echo "\n";

        $is_ok = $this->getPhpunit()->run($test_file);

        if ($is_ok) {
            $this->getFailsSession()->delete($test_file);
        } else {
            $this->getFailsSession()->set($test_file, 1);
        }

        if ($this->result && !$is_ok) {
            $this->result = false;
        }

        $this->registerExecutedTest($test_file);
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

    protected function report()
    {
        if ($this->hasExecutedTests()) {

            echo "\n";
            echo "\n";
            echo $this->result ? '[OK]' : '[FAIL]';
            echo "\n";

            if ($this->getFailsSession()->count()) {

                echo "\n";
                echo "[NOTICE] [FAIL] You have fail(s):";

                foreach ($this->getFailsSession()->keys() as $name) {
                    echo "\n";
                    echo $name;
                }

                echo "\n";

            }

            $this->notify();
        }
    }

    /**
     * @return int
     */
    protected function hasExecutedTests()
    {
        return count($this->executed_tests);
    }

    protected function handleCallbacks()
    {
        if ($this->hasExecutedTests()) {

            if (!$this->result) {
                if ($this->on_fail) {
                    $this->getProcessor()->run($this->on_fail);
                }
            } else {

                if ($this->on_pass) {
                    $this->getProcessor()->run($this->on_pass);
                }

            }
        }
    }

    /**
     * @return ChangeProvider
     */
    protected function getChangeProvider()
    {
        if (!$this->change_provider) {
            $this->change_provider = new ChangeProvider(new GitChangeFinder($this->getProcessor()), new Session($this->base_tmp . '_changes'));
        }

        return $this->change_provider;
    }


    /**
     * @param \Hot\PHPUnit\ProcessorInterface $processor
     */
    public function setProcessor(ProcessorInterface $processor)
    {
        $this->processor = $processor;
    }

    /**
     * @return \Hot\PHPUnit\ProcessorInterface
     */
    public function getProcessor()
    {
        if (!$this->processor) {
            $this->processor = new Processor();
        }
        return $this->processor;
    }

    /**
     * @param int $test_similarity
     */
    public function setTestSimilarity($test_similarity)
    {
        $this->getTestFinder()->setTestSimilarity($test_similarity);
    }

    /**
     * @param mixed $on_fail
     */
    public function setOnFail($on_fail)
    {
        if ($on_fail) {
            $this->on_fail = $on_fail;
        }
    }

    /**
     * @param mixed $on_pass
     */
    public function setOnPass($on_pass)
    {
        if ($on_pass) {
            $this->on_pass = $on_pass;
        }
    }

    /**
     * @param mixed $notify
     */
    public function setNotify($notify)
    {
        if ($notify) {
            $this->notify = $notify;
        }
    }


    /**
     * @return Session
     */
    protected function getFailsSession()
    {
        if (!$this->fails_session) {
            $this->fails_session = new Session($this->base_tmp . '_fails');
            $this->fails_session->load();
        }
        return $this->fails_session;
    }

    /**
     * @return TestFinder
     */
    protected function getTestFinder()
    {

        if (!$this->test_finder) {
            $this->test_finder = new TestFinder($this->getProcessor());
        }

        return $this->test_finder;
    }

    protected function notify()
    {
        if ($this->notify && class_exists('\Hot\Notify\Notify')) {
            $notify = new \Hot\Notify\Notify();
            $status = $this->result ? 'OK' : 'FAIL';
            $notify->notify($status, 'Test(s)');
        }
    }

    /**
     * @return array
     */
    protected function getPhpunitFilterFiles()
    {

        $filter_files = array_merge(
            $this->getChangeProvider()->getChanges(),
            $this->getTestFinder()->findTests($this->getChangeProvider()->getChanges())
        );

        return array_unique($filter_files);
    }

    /**
     * @return PHPUnit
     */
    protected function getPhpunit()
    {
        if (!$this->phpunit) {
            $this->phpunit = new PHPUnit($this->request, $this->getProcessor());
        }

        return $this->phpunit;
    }

    /**
     * @param $test_file
     */
    protected function registerExecutedTest($test_file)
    {
        $this->executed_tests[realpath($test_file)] = 1;
    }

    /**
     * @param $test_file
     * @return bool
     */
    protected function isExecutedTest($test_file)
    {
        return isset($this->executed_tests[realpath($test_file)]);
    }


}