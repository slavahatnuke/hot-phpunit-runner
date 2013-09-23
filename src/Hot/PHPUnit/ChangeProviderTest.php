<?php


namespace Hot\PHPUnit;


class ChangeProviderTest extends \PHPUnit_Framework_TestCase
{

    protected $uniq_file;

    protected $session_file;

    protected function setUp()
    {
        $this->uniq_file = uniqid();
        $this->session_file = sys_get_temp_dir() . '/' . uniqid();
    }

    protected function tearDown()
    {
        if (file_exists($this->uniq_file)) {
            unlink($this->uniq_file);
        }

        if (file_exists($this->session_file)) {
            unlink($this->session_file);
        }
    }

    /**
     * @test
     */
    public function shouldReturnChanges()
    {
        $file = getcwd() . '/' . $this->uniq_file;

        $provider = $this->newProvider();
        $changes = $provider->getChanges();

        $this->assertFalse(in_array($file, $changes));

        file_put_contents($file, 1);

        $changes = $provider->getChanges();

        $this->assertTrue(in_array($file, $changes));
    }


    /**
     * @test
     */
    public function shouldNotReturnChangesIfWasCommit()
    {
        $file = getcwd() . '/' . $this->uniq_file;

        $provider = $this->newProvider();


        file_put_contents($file, 1);

        $changes = $provider->getChanges();
        $this->assertTrue(in_array($file, $changes));

        $provider->commit();
        $changes = $provider->getChanges();
        $this->assertFalse(in_array($file, $changes));

        $all_changes = $provider->getAllChanges();
        $this->assertTrue(in_array($file, $all_changes));
    }


    /**
     * @test
     */
    public function shouldReturnChangesIfFileContentWasChanged()
    {
        $file = getcwd() . '/' . $this->uniq_file;

        $provider = $this->newProvider();
        file_put_contents($file, 1);

        $provider->commit();

        $changes = $provider->getChanges();
        $this->assertFalse(in_array($file, $changes));

        file_put_contents($file, 2);

        $changes = $provider->getChanges();
        $this->assertTrue(in_array($file, $changes));
    }


    /**
     * @test
     */
    public function shouldReturnFilesWhenCleaned()
    {
        $file = getcwd() . '/' . $this->uniq_file;

        $provider = $this->newProvider();

        file_put_contents($file, 1);

        $changes = $provider->getChanges();
        $this->assertTrue(in_array($file, $changes));

        $provider->commit();

        $changes = $provider->getChanges();
        $this->assertFalse(in_array($file, $changes));

        $provider->reset();

        $changes = $provider->getChanges();
        $this->assertTrue(in_array($file, $changes));
    }


    /**
     * @return ChangeProvider
     */
    protected function newProvider()
    {
        return new ChangeProvider(new GitChangeFinder(new Processor()), new Session($this->session_file));
    }
}
