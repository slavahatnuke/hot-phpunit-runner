<?php


namespace Hot\PHPUnit;

class Request extends Map
{

    protected $bin;

    public function __construct($options = array(), $bin = null)
    {
        $this->bin = $bin;
        parent::__construct();
        $this->load($options);
    }

    /**
     * @return null
     */
    public function getBin()
    {
        return $this->bin;
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