<?php
namespace Hot\PHPUnit;

interface ProcessorInterface
{
    public function run($command);

    public function execute($command);
}