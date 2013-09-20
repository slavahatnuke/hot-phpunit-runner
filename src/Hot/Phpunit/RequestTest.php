<?php


namespace Hot\Phpunit;


class RequestTest extends \PHPUnit_Framework_TestCase {
    /**
     * @test
     */
    public function shouldConstruct()
    {
        $this->assertTrue(new Request() instanceof Map);
    }

    /**
     * @test
     */
    public function should1()
    {
        $request = new Request(['--name']);
        $this->assertTrue($request->get('name'));
    }

    /**
     * @test
     */
    public function should2()
    {
        $request = new Request(['--name=val']);
        $this->assertEquals('val', $request->get('name'));
    }

    /**
     * @test
     */
    public function should3()
    {
        $request = new Request(['--name="val wally"']);
        $this->assertEquals('val wally', $request->get('name'));
    }


}
