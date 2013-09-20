<?php


namespace Hot\PHPUnit;


class RequestTest extends \PHPUnit_Framework_TestCase
{
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

    /**
     * @test
     */
    public function getArray()
    {
        $request = new Request(['--name1=n1', '--name2=n2', '--name3=n3']);
        $expected_array = ['name1' => 'n1', 'name3' => 'n3'];
        $this->assertEquals($expected_array, $request->getHash(['name1', 'name3']));
    }


    /**
     * @test
     */
    public function generateBin()
    {
        $request = new Request([], 'bin/bin');

        $bin = $request->generateBin([
            'option-1' => 'value-1',
            'option-2' => 'value-2'
        ]);

        $this->assertEquals("bin/bin --'option-1'='value-1' --'option-2'='value-2'", $bin);
    }


}
