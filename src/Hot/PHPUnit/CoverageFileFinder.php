<?php


namespace Hot\PHPUnit;


class CoverageFileFinder implements FinderInterface {

    protected $change_provider;
    
    protected $test_finder;

    public function __construct(ChangeProvider $change_provider, TestFinder $test_finder)
    {

        $this->change_provider = $change_provider;
        $this->test_finder = $test_finder;
    }

    /**
     * @return array
     */
    public function find()
    {

        $changes = $this->change_provider->getChanges();

        $files = array_merge(
            $changes,
            $this->test_finder->findTests($changes)
        );


        $result = [];

        foreach (array_unique($files) as $file) {
            if ($this->isPhp($file)) {
                $result[] = $file;
            }
        }

        return array_values($result);
    }


    /**
     * @param $file
     * @return int
     */
    protected function isPhp($file)
    {
        return preg_match('/\.php/i', $file);
    }
}