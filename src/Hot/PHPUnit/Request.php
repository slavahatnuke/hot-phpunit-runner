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

    public function getHash($names)
    {
        $result = [];

        foreach ($names as $name) {
            if ($this->has($name)) {
                $result[$name] = $this->get($name);
            }
        }

        return $result;
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
            } else if (preg_match('/--(.+)/', $option, $a)) {
                list($x, $key) = $a;
                $this->set($this->trim($key), true);
            }

        }

    }

    public function generateBin($options)
    {
        $bin = $this->getBin();

        foreach ($options as $option => $value) {
            $bin .= " --{$option}=" . "'" . $value . "'";
        }

        return $bin;

    }

}