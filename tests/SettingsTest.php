<?php

use Illuminate\Container\Container;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Events\Dispatcher;
use Oriceon\Settings\Repositories\DatabaseRepository;
use Oriceon\Settings\Repositories\CacheRepository;
use PHPUnit\Framework\TestCase;

class SettingsTest extends TestCase
{
	const SETTINGS_TABLE   = 'settings__lists';
	const SETTINGS_COL_KEY = 'setting_key';
	const SETTINGS_COL_VAL = 'setting_value';

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
		$this->db = $this->initDb();

		$this->config = [
			'db_table'   => self::SETTINGS_TABLE,
			'cache_file' => $this->settings_file(),
			'fallback'   => false
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
	public function testSetByOneKey()
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
    public function testSetByDotKey()
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
    public function testSetArrayAsAValue()
    {
        $value = ['key' => 'value'];

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
    public function testGet()
    {
        $key   = 'key';
        $value = 'value';

        $this->settings->set($key, $value);

        $this->assertEquals($value, $this->settings->get($key));
    }

    /**
     *
     */
    public function testGetAll()
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
    public function testHasKey()
    {
        $this->settings->set('key1', 'value1');

        $this->assertTrue($this->settings->has('key1'));
        $this->assertFalse($this->settings->has('key2'));
    }

    /**
     *
     */
    public function testHasKeyWithoutCache()
    {
        $this->settings->set('key1', 'value1');

        $this->assertTrue($this->settings->has('key1'));
        $this->assertFalse($this->settings->has('key2'));

        @unlink($this->settings_file());

        $this->assertTrue($this->settings->has('key1'));
        $this->assertFalse($this->settings->has('key2'));
    }

    /**
     *
     */
    public function testForget()
    {
        $this->settings->set('key', 'value');
        $this->settings->forget('key');

        $this->assertNull($this->settings->get('key'));
    }

    /**
     *
     */
    public function testFlush()
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
		Capsule::schema()->drop(self::SETTINGS_TABLE);
		@unlink($this->settings_file());
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


    private function settings_file()
    {
        return dirname(__DIR__) . '/tests/settings.json';
    }
}
