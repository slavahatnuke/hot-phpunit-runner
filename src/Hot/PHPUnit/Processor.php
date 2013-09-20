<?php


namespace Hot\PHPUnit;


class Processor implements ProcessorInterface {
    public function run($command){
        $status = null;
        passthru($command, $status);
        return !$status;
    }

    public function execute($command){
        $status = null;
        $result = [];
        exec($command, $result, $status);
        return !$status ? $result : null;
    }
}