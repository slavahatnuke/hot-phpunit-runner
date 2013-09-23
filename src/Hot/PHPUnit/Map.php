<?php
namespace Hot\PHPUnit;

class Map implements \IteratorAggregate, \Countable
{

    protected $data = array();

    public function __construct($data = array())
    {
        if (is_array($data)) {
            $this->data = $data;
        }
    }

    public function has($name)
    {
        return array_key_exists($name, $this->data);
    }

    public function get($name)
    {
        return $this->has($name) ? $this->data[$name] : null;
    }

    public function set($name, $value)
    {
        return $this->data[$name] = $value;
    }

    public function delete($name)
    {
        if($this->has($name))
            unset($this->data[$name]);
    }

    public function keys(){
        return array_keys($this->data);
    }

    public function getIterator()
    {
        return new \ArrayIterator($this->data);
    }

    public function count()
    {
        return $this->getIterator()->count();
    }
}
