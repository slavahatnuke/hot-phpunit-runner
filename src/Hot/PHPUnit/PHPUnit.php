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

    protected $generated_coverage_file;

    public function __construct(Map $options, ProcessorInterface $processor, FinderInterface $coverage_file_finder)
    {
        $this->options = $options;
        $this->processor = $processor;
        $this->coverage_file_finder = $coverage_file_finder;
    }

    public function run($test)
    {


        $result = $this->processor->run($this->generateBin($test));

        $this->mergeCoverage();
        $this->removeGeneratedCoverageFile();


        return $result;
    }

    /**
     * @param $test
     * @return string
     */
    public function generateBin($test)
    {

        $this->generateCoverageFile();

        $cmd = $this->options->has('phpunit-bin') ? $this->options->get('phpunit-bin') : 'phpunit';

        if ($this->options->has('phpunit-options')) {
            $cmd .= ' ' . $this->options->get('phpunit-options');
        }

        if ($this->options->has('coverage')) {

            if ($this->isCoverageMode()) {
                $cmd .= ' --coverage-clover ' . $this->generated_coverage_file;
            } else {
                $cmd .= ' --coverage-clover ' . $this->getCoverageFile();
            }

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

    /**
     * @return null|string
     */
    protected function getCoverageFile()
    {
        $file = $this->options->get('coverage');
        $file = in_array($file, [true, 1, "1", "true"], true) ? 'coverage.xml' : $file;
        return $file;
    }

    protected function generateCoverageFile()
    {
        if ($this->isCoverageMode() && !$this->generated_coverage_file) {
            $this->generated_coverage_file = $this->getCoverageFile() . '_' . uniqid() . '.xml';
        }
    }

    protected function removeGeneratedCoverageFile()
    {
        if ($this->isCoverageMode()) {
            if ($this->generated_coverage_file && file_exists($this->generated_coverage_file)) {
                unlink($this->generated_coverage_file);
                $this->generated_coverage_file = null;
            }

        }
    }

    protected function mergeCoverage()
    {
        if ($this->generated_coverage_file && $this->isCoverageMode() && file_exists($this->generated_coverage_file)) {
            if (file_exists($this->getCoverageFile())) {
                $this->mergeXmlCoverage();
            } else {
                rename($this->generated_coverage_file, $this->getCoverageFile());
            }

        }

    }


    protected function mergeXmlCoverage()
    {
        $coverage = new \DOMDocument();
        $coverage->load($this->getCoverageFile());

        $new_coverage = new \DOMDocument();
        $new_coverage->load($this->generated_coverage_file);

        $new_files = $new_coverage->getElementsByTagName('file');

        foreach ($new_files as $new_file) {

            $file_name = $new_file->getAttribute('name');

//            echo $file_name;
//            echo "\n\n";

            $coverage_x_path = new \DOMXPath($coverage);
            $exist_files = $coverage_x_path->query("//*[@name='{$file_name}']");

            if ($exist_files->length) {
                //update
                foreach ($exist_files as $exist_file) {
                    $exist_package = $exist_file->parentNode;
                    $exist_package->removeChild($exist_file);
                    $exist_package->appendChild($coverage->importNode($new_file, true));
                }
            } else {

                // add to exist package
                $new_package = $new_file->parentNode;
                $new_package_name = $new_package->getAttribute('name');

                $coverage_x_path = new \DOMXPath($coverage);
                $exist_packages = $coverage_x_path->query("//*[@name='{$new_package_name}']");

                if ($exist_packages->length) {

                    foreach ($exist_packages as $exist_package) {
                        $exist_package->appendChild($coverage->importNode($new_file, true));
                    }

                } else {
                    //add to project
                    $coverage_projects = $coverage->getElementsByTagName('project');

                    foreach ($coverage_projects as $coverage_project) {
                        $coverage_project->appendChild($coverage->importNode($new_package, true));
                    }

                }


            }


        }


//        unlink($this->getCoverageFile());
//        rename($this->generated_coverage_file, $this->getCoverageFile());
        $coverage->save($this->getCoverageFile());

    }
}