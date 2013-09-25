<?php


namespace Hot\PHPUnit;


class PHPUnit
{

    /**
     * @var Map
     */
    protected $options;

    /**
     * @var ProcessorInterface
     */
    protected $processor;

    /**
     * @var CoverageFileFinder
     */
    protected $coverage_file_finder;

    protected $generated_config_file;

    public function __construct(Map $options, ProcessorInterface $processor, FinderInterface $coverage_file_finder)
    {
        $this->options = $options;
        $this->processor = $processor;
        $this->coverage_file_finder = $coverage_file_finder;
    }

    public function run($test)
    {
        return $this->processor->run($this->generateBin($test));
    }

    /**
     * @param $test
     * @return string
     */
    public function generateBin($test)
    {
        $cmd = $this->options->has('phpunit-bin') ? $this->options->get('phpunit-bin') : 'phpunit';


        if ($this->options->has('phpunit-options')) {
            $cmd .= ' ' . $this->options->get('phpunit-options');
        }

        if ($this->options->has('coverage')) {

            $phpunit_coverage = $this->options->get('coverage');
            $phpunit_coverage = in_array($phpunit_coverage, [true, 1, "1", "true"], true) ? 'coverage.xml' : $phpunit_coverage;

            $cmd .= ' --coverage-clover ' . $phpunit_coverage;
        }


        if ($this->options->has('config')) {

            if ($this->isCoverageMode()) {
                $cmd .= " -c {$this->generated_config_file}";
            } else {
                $config_file = $this->options->get('config');
                $cmd .= " -c {$config_file}";
            }
        }


        $cmd .= ' ' . $test;
        return $cmd;
    }

    /**
     * @return bool
     */
    public function isCoverageMode()
    {
        return $this->options->has('coverage') && $this->options->has('config') && is_file($this->options->get('config'));
    }


    public function beforeHandle()
    {
        if ($this->isCoverageMode()) {
            $this->generated_config_file = $this->options->get('config') . '_' . uniqid() . '.xml';
            $this->buildPhpunitConfigFile($this->coverage_file_finder->find(), $this->generated_config_file);
            return $this->generated_config_file;
        }
    }


    public function afterHandle()
    {
        if ($this->isCoverageMode()) {
            if ($this->generated_config_file && file_exists($this->generated_config_file)) {
                unlink($this->generated_config_file);
            }
        }
    }


    protected function buildPhpunitConfigFile($files, $result_file)
    {

        $phpunit_config_file = $this->options->get('config');

        $phpunit_config = new \DOMDocument();


        $phpunit_config->load($phpunit_config_file);
        $filters = $phpunit_config->getElementsByTagName('filter');

        $phpunit = null;
        $filter = null;

        foreach ($filters as $filter) {
            $phpunit = $filter->parentNode;
            $filter->parentNode->removeChild($filter);
        }

        if (!$filter) {
            $phpunits = $phpunit_config->getElementsByTagName('phpunit');
            foreach ($phpunits as $phpunit) ;
        }


        if ($phpunit) {

            $new_filter = $phpunit_config->createElement('filter');
            $wl = $phpunit_config->createElement('whitelist');
            $new_filter->appendChild($wl);

            foreach ($files as $x_file) {
                $wl->appendChild($phpunit_config->createElement('file', $x_file));
            }

            $phpunit->appendChild($new_filter);
        }

        $phpunit_config->save($result_file);

    }
}