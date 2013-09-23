<?php


namespace Hot\PHPUnit;


class SessionTest extends \PHPUnit_Framework_TestCase {

    /**
     * @test
     */
    public function shouldConstructWithFileAndData()
    {
        $session = new Session($this->getTmpFile(), ['x' => 'y']);
        $this->assertEquals('y', $session->get('x'));
    }

    /**
     * @test
     */
    public function shouldSaveAndLoadChanges()
    {
        $file = $this->getTmpFile();
        $session = new Session($file);

        $this->assertFalse($session->has('key'));
        $session->set('key', 'value');
        $this->assertTrue($session->has('key'));
        $this->assertEquals('value', $session->get('key'));

        $session->save();

        $session = new Session($file);
        $this->assertFalse($session->has('key'));

        $session->load();

        $this->assertTrue($session->has('key'));
        $this->assertEquals('value', $session->get('key'));
    }



    /**
     * @test
     */
    public function shouldRemove()
    {
        $file = $this->getTmpFile();

        $session = new Session($file);
        $session->set('x', 'y');
        $session->save();

        $session_new = new Session($file);
        $session_new->load();


        $this->assertEquals('y', $session_new->get('x'));
        
        $session->remove();

        $session_new = new Session($file);
        $session_new->load();

        $this->assertEquals(null, $session_new->get('x'));
    }

    /**
     * @return string
     */
    protected function getTmpFile()
    {
        return sys_get_temp_dir() . '/' . md5(time() . rand());
    }

}
