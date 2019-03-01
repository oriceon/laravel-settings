<?php

use Oriceon\Settings\Repositories\CacheRepository;
use PHPUnit\Framework\TestCase;

class CacheTest extends TestCase
{
    /**
     * @var
     */
    protected $cacheFile;

    /**
     * @var
     */
    protected $cache;

    /**
     *
     */
    protected function setUp()
    {
        parent::setUp();

        $this->cacheFile = __DIR__ . DIRECTORY_SEPARATOR . 'settings.json';

        file_put_contents($this->cacheFile, '{}');

        $this->cache = new CacheRepository($this->cacheFile);
    }

    /** @test */
    public function set_one_key()
    {
        $this->cache->set('key', 'value');

        $contents = file_get_contents($this->cacheFile);

        $this->assertEquals('{"key":"value"}', $contents);
    }

    /** @test */
    public function set_dot_key()
    {
        $this->cache->set('key1.key2', 'value');

        $contents = file_get_contents($this->cacheFile);

        $this->assertEquals('{"key1":{"key2":"value"}}', $contents);
    }

    /** @test */
    public function set_array()
    {
        $set = ['value1' => 1, 'value2' => 2];
        $this->cache->set('key', $set);

        $contents = file_get_contents($this->cacheFile);

        $this->assertEquals('{"key":{"value1":1,"value2":2}}', $contents);
        $this->assertEquals($this->cache->get('key'), $set);
    }

    /** @test */
    public function get_key()
    {
        $this->cache->set('key', 'value');

        $this->assertEquals('value', $this->cache->get('key'));
    }

    /** @test */
    public function get_all_keys()
    {
        $this->cache->set('key1', 'value1');
        $this->cache->set('key2', 'value2');

        $this->assertEquals(['key1' => 'value1', 'key2' => 'value2'], $this->cache->getAll());
    }

    /** @test */
    public function has_key()
    {
        $this->cache->set('key', 'value');

        $this->assertTrue($this->cache->has('key'));
    }

    /** @test */
    public function forget_key()
    {
        $this->cache->set('key', 'value');
        $this->cache->forget('key');

        $this->assertNull($this->cache->get('key'));
    }

    /** @test */
    public function flush()
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
        parent::tearDown();

        @unlink($this->cacheFile);
    }

}