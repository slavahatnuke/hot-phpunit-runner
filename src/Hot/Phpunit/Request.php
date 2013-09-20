<?php


namespace Hot\Phpunit;

class Request extends Map
{

    public function __construct($options = array())
    {
        parent::__construct();
        $this->load($options);
    }

    protected function trim($value)
    {
        return trim($value, ' "\'');
    }

    protected function load($options)
    {

        foreach ($options as $option) {

            if (preg_match('/--(.+?)=(.+)/', $option, $a)) {
                list($x, $key, $value) = $a;
                $this->set($this->trim($key), $this->trim($value));
            }

            if (preg_match('/--(.+)/', $option, $a)) {
                list($x, $key) = $a;
                $this->set($this->trim($key), true);
            }

        }

    }

}