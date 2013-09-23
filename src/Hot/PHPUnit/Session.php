<?php


namespace Hot\PHPUnit;


class Session extends Map {

    protected $file;

    public function __construct($file, $data = [])
    {
        $this->file = $file;
        parent::__construct($data);
    }

    public function save(){
        file_put_contents($this->file, json_encode($this->data));
    }

    public function load(){

        if (!$this->hasSession()) {
            $this->save();
        }

        $this->data = array_merge($this->data, json_decode(file_get_contents($this->file), 1));
    }

    public function remove(){

        if ($this->hasSession()) {
            unlink($this->file);
        }

        $this->data = [];
    }


    /**
     * @return bool
     */
    protected function hasSession()
    {
        return file_exists($this->file);
    }

}