<?php


namespace Hot\PHPUnit;


class GitChangeFinder implements FinderInterface
{

    protected $processor;

    public function __construct(ProcessorInterface $processor)
    {
        $this->processor = $processor;
    }

    public function find()
    {
        $result = [];

        $changes = $this->processor->execute('git status -s');

        $changes = $changes ? $changes : [];
        array_walk($changes, 'trim');

        foreach ($changes as $file) {

            $a = [];
            if (preg_match('/.+?\s(.+)$/', $file, $a)) {

                $file = realpath($a[1]);

                if ($file) {
                    $result[] = $file;
                }
            }

        }

        return $result;
    }
}