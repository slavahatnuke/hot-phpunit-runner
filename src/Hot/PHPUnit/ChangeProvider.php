<?php


namespace Hot\PHPUnit;


class ChangeProvider
{

    protected $finder;

    protected $session;

    public function __construct(FinderInterface $finder, Session $session)
    {
        $this->finder = $finder;
        $this->session = $session;
    }

    public function getChanges()
    {

        $this->session->load();

        $result = [];

        $files = array_merge($this->session->keys(), $this->getAllChanges());


        foreach ($files as $file) {

            if (file_exists($file)) {

                if($this->isChanged($file)){
                    $result[] = $file;
                }

            }
        }


        return array_unique($result);
    }

    /**
     * @return array
     */
    public function getAllChanges()
    {
        return $this->finder->find();
    }

    public function reset()
    {
        $this->session->remove();
        $this->session->save();
    }

    public function commit()
    {



        foreach ($this->getChanges() as $file) {
            if (file_exists($file)) {
                $this->session->set($file, $this->createFileHash($file));
            }
        }

        $this->session->save();
    }

    /**
     * @param $file
     * @return string
     */
    protected function createFileHash($file)
    {
        return md5(file_get_contents($file));
    }

    /**
     * @param $file
     * @param $result
     * @return array
     */
    protected function isChanged($file)
    {
        if ($this->session->has($file)) {
            if ($this->session->get($file) != $this->createFileHash($file)) {
                return true;
            }
        } else {
            return true;
        }

        return false;
    }

}