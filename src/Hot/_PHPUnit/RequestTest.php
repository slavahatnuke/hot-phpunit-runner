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
    public function shouldDefineValueWithTrue()
    {
        $request = new Request(['--name']);
        $this->assertTrue($request->get('name'));
    }

    /**
     * @test
     */
    public function shouldParseValue()
    {
        $request = new Request(['--name=val']);
        $this->assertEquals('val', $request->get('name'));
    }

    /**
     * @test
     */
    public function shouldParseQuotedValue()
    {
        $request = new Request(['--name="val wally"']);
        $this->assertEquals('val wally', $request->get('name'));
    }

    /**
     * @test
     */
    public function getBin()
    {
        $bin = 'bin/bin/bin';

        $request = new Request([], $bin);
        $this->assertSame($bin, $request->getBin());
    }


}
