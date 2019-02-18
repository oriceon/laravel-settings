<?php

use Illuminate\Container\Container;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Events\Dispatcher;
use Oriceon\Settings\Repositories\DatabaseRepository;
use Oriceon\Settings\Repositories\CacheRepository;
use PHPUnit\Framework\TestCase;

class SettingsTest extends TestCase
{
    const SETTINGS_CONNECTION = '';
    const SETTINGS_TABLE      = 'settings__lists';
    const SETTINGS_COL_KEY    = 'setting_key';
    const SETTINGS_COL_VAL    = 'setting_value';

    /**
     * @var
     */
    protected $cacheFile;

    /**
     * @var
     */
    protected $settings;

    /**
     * @var
     */
    protected $db;

    /**
     * @var
     */
    protected $config;

    /**
     *
     */
    protected function setUp()
    {
        parent::setUp();

        $this->cacheFile = __DIR__ . DIRECTORY_SEPARATOR . 'settings.json';

        $this->db = $this->initDb();

        $this->config = [
            'db_connection' => self::SETTINGS_CONNECTION,
            'db_table'      => self::SETTINGS_TABLE,
            'cache_file'    => $this->cacheFile,
            'fallback'      => false
        ];

        $this->settings = new DatabaseRepository(
            $this->db,
            new CacheRepository($this->config['cache_file']),
            $this->config
        );
    }

    /**
     *
     */
    public function test_set_one_normal_key()
    {
        $key   = 'key';
        $value = 'value';

        $this->settings->set($key, $value);


        $row = $this->db->table(self::SETTINGS_TABLE)
            ->where(self::SETTINGS_COL_KEY, $key)
            ->first([self::SETTINGS_COL_VAL]);

        $this->assertEquals($value, json_decode($row->{self::SETTINGS_COL_VAL}, true));
        $this->assertEquals($value, $this->settings->get('key'));
    }

    /**
     *
     */
    public function test_set_a_dot_key()
    {
        $value = 'value';

        $this->settings->set('key1.key2', $value);


        $row = $this->db->table(self::SETTINGS_TABLE)
            ->where(self::SETTINGS_COL_KEY, 'key1')
            ->first([self::SETTINGS_COL_VAL]);

        $this->assertEquals(['key2' => $value], json_decode($row->{self::SETTINGS_COL_VAL}, true));
        $this->assertEquals($value, $this->settings->get('key1.key2'));
    }

    /**
     *
     */
    public function test_set_array_as_a_value()
    {
        $value = ['array_key' => 'array_value'];

        $this->settings->set('key', $value);


        $row = $this->db->table(self::SETTINGS_TABLE)
            ->where(self::SETTINGS_COL_KEY, 'key')
            ->first([self::SETTINGS_COL_VAL]);

        $this->assertEquals($value, json_decode($row->{self::SETTINGS_COL_VAL}, true));
        $this->assertEquals($value, $this->settings->get('key'));
    }

    /**
     *
     */
    public function test_get_key()
    {
        $key   = 'key';
        $value = 'value';

        $this->settings->set($key, $value);

        $this->assertEquals($value, $this->settings->get($key));
    }

    /**
     *
     */
    public function test_get_all()
    {
        $this->settings->set('key1', 'value1');
        $this->settings->set('key2', 'value2');

        $this->assertEquals('value1', $this->settings->get('key1'));
        $this->assertEquals('value2', $this->settings->get('key2'));
        $this->assertEquals(['key1' => 'value1', 'key2' => 'value2'], $this->settings->getAll());
    }

    /**
     *
     */
    public function test_has_key()
    {
        $this->settings->set('key1', 'value1');

        $this->assertTrue($this->settings->has('key1'));
        $this->assertFalse($this->settings->has('key2'));
    }

    /**
     *
     */
    public function test_has_key_without_cache()
    {
        $this->settings->set('key1', 'value1');

        $this->assertTrue($this->settings->has('key1'));
        $this->assertFalse($this->settings->has('key2'));

        @unlink($this->cacheFile);

        $this->assertTrue($this->settings->has('key1'));
        $this->assertFalse($this->settings->has('key2'));
    }

    /**
     *
     */
    public function test_forget()
    {
        $this->settings->set('key', 'value');
        $this->settings->forget('key');

        $this->assertNull($this->settings->get('key'));
    }

    /**
     *
     */
    public function test_flush()
    {
        $this->settings->set('key', 'value');
        $this->settings->flush();

        $this->assertEquals([], $this->settings->getAll());
    }


    /**
     *
     */
    protected function tearDown()
    {
        parent::tearDown();

        Capsule::schema()->drop(self::SETTINGS_TABLE);
        @unlink($this->cacheFile);
    }

    /**
     * @return \Illuminate\Database\DatabaseManager
     */
    private function initDb()
    {
        $capsule = new Capsule;

        $capsule->addConnection([
            'driver'   => 'sqlite',
            'host'     => 'localhost',
            'database' => ':memory:',
            'prefix'   => '',
        ]);

        $capsule->setEventDispatcher(new Dispatcher(new Container));
        $capsule->setAsGlobal();
        $capsule->bootEloquent();

        Capsule::schema()->create(self::SETTINGS_TABLE, function ($table)
        {
            $table->string(self::SETTINGS_COL_KEY)->index()->unique();
            $table->json(self::SETTINGS_COL_VAL)->nullable();
        });

        return $capsule->getDatabaseManager();
    }

}