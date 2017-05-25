<?php

use Oriceon\Settings\Repositories\CacheRepository;
use PHPUnit\Framework\TestCase;

class CacheTest extends TestCase
{
    /**
     * @var
     */
    protected $cache;

    /**
     * @var
     */
    protected $cacheFile;

    /**
     *
     */
    protected function setUp()
    {
        $this->cacheFile = $this->settings_file();
        $this->cache     = new CacheRepository($this->cacheFile);
    }

    /**
     *
     */
    public function testSetByOneKey()
    {
        $this->cache->set('key', 'value');

        $contents = file_get_contents($this->cacheFile);

        $this->assertEquals('{"key":"value"}', $contents);
    }

    /**
     *
     */
    public function testSetByDotKey()
    {
        $this->cache->set('key1.key2', 'value');

        $contents = file_get_contents($this->cacheFile);

        $this->assertEquals('{"key1":{"key2":"value"}}', $contents);
    }

    /**
     *
     */
    public function testSetArray()
    {
        $set = ['value1' => 1, 'value2' => 2];
        $this->cache->set('key', $set);

        $contents = file_get_contents($this->cacheFile);

        $this->assertEquals('{"key":{"value1":1,"value2":2}}', $contents);
        $this->assertEquals($this->cache->get('key'), $set);
    }

    /**
     *
     */
    public function testGet()
    {
        $this->cache->set('key', 'value');

        $this->assertEquals('value', $this->cache->get('key'));
    }

    /**
     *
     */
    public function testGetAll()
    {
        $this->cache->set('key1', 'value1');
        $this->cache->set('key2', 'value2');

        $this->assertEquals(['key1' => 'value1', 'key2' => 'value2'], $this->cache->getAll());
    }

    /**
     *
     */
    public function testHasKey()
    {
        $this->cache->set('key', 'value');

        $this->assertTrue($this->cache->has('key'));
    }

    /**
     *
     */
    public function testForget()
    {
        $this->cache->set('key', 'value');
        $this->cache->forget('key');

        $this->assertNull($this->cache->get('key'));
    }

    /**
     *
     */
    public function testFlush()
    {
        $this->cache->set('key', 'value');
        $this->cache->flush();

        $this->assertEquals([], $this->cache->getAll());
    }


    /**
     *
     */
    protected function tearDown()
    {
        @unlink($this->settings_file());
    }


    private function settings_file()
    {
        return dirname(__DIR__) . '/tests/settings.json';
    }
}
