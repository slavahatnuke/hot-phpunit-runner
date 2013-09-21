<?php


namespace Hot\PHPUnit;


class MapTest extends \PHPUnit_Framework_TestCase {

    /**
     * @test
     */
    public function shouldBeConstructed()
    {
        new Map();
    }

    /**
     * @test
     */
    public function shouldWork()
    {
        $map = new Map(['k' => 'v']);

        $this->assertTrue($map->has('k'));
        $this->assertEquals('v', $map->get('k'));

        $map->set('k', 'v2');
        $this->assertNotEquals('v', $map->get('k'));
        $this->assertEquals('v2', $map->get('k'));
        
        $this->assertEquals(1, count($map));

    }

    /**
     * @test
     */
    public function hasValue()
    {
        $map = new Map(['k' => 'v']);

        $this->assertTrue($map->has('k'));
        $this->assertFalse($map->has('k1'));
    }

    /**
     * @test
     */
    public function setValue()
    {

        $map = new Map(['k' => 'v']);

        $this->assertEquals('v', $map->get('k'));

        $map->set('k', 'v2');
        $this->assertEquals('v2', $map->get('k'));
    }

    /**
     * @test
     */
    public function countValues()
    {
        $map = new Map(['k' => 'v']);
        $this->assertEquals(1, count($map));
    }

    /**
     * @test
     */
    public function keys()
    {
        $map = new Map(['k' => 'v']);
        $this->assertEquals(['k'], $map->keys());
    }

    /**
     * @test
     */
    public function delete()
    {
        $map = new Map(['k' => 'v']);
        $this->assertTrue($map->has('k'));
        $map->delete('k');
        $this->assertFalse($map->has('k'));
    }

}
