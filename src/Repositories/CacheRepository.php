<?php

namespace Oriceon\Settings\Repositories;

class CacheRepository
{
	/**
	 * Path to cache file
	 *
	 * @var string
	 */
	protected $cacheFile;

	/**
	 * Cached Settings
	 *
	 * @var array
	 */
	protected $settings;


	/**
	 * Constructor
	 *
	 * @param string $cacheFile
	 */
	public function __construct($cacheFile)
	{
		$this->cacheFile = $cacheFile;
		$this->checkCacheFile();

		$this->settings = $this->getAll();
	}

	/**
	 * Sets a value
	 *
	 * @param $key
	 * @param $value
	 *
	 * @return mixed
	 */
	public function set($key, $value)
	{
        set_nested_array_value($this->settings, $key, $value);

		$this->store();

		return $value;
	}

	/**
	 * Gets a value
	 *
	 * @param      $key
	 * @param null $default
	 *
	 * @return mixed
	 */
	public function get($key, $default = null)
	{
        $value = $this->settings;
        $value = set_nested_array_value($value, $key);

        if ( ! is_null($value))
        {
            return $value;
        }

        return $default;
	}

    /**
     * Gets all cached settings
     *
     * @return array
     */
    public function getAll()
    {
        $values = json_decode(file_get_contents($this->cacheFile), true);

        if ( ! is_null($values))
        {
            return $values;
        }

        return [];
    }

	/**
	 * Checks if $key is cached
	 *
	 * @param $key
	 *
	 * @return bool
	 */
	public function has($key)
	{
        return multi_key_exists(explode('.', $key), $this->settings);
	}

    /**
     * Removes a value
     *
     * @param $key
     *
     * @return void
     */
	public function forget($key)
	{
        array_unset($this->settings, $key);

		$this->store();
	}

	/**
	 * Removes all values
	 *
	 * @return void
	 */
	public function flush()
	{
		file_put_contents($this->cacheFile, json_encode([]));
	}


	/**
	 * Checks if the cache file exists and creates it if not
	 *
	 * @return void
	 */
	private function checkCacheFile()
	{
		if ( ! file_exists($this->cacheFile))
		{
			$this->flush();
		}
	}

    /**
     * Stores all settings to the cache file
     *
     * @return void
     */
    private function store()
    {
        file_put_contents($this->cacheFile, json_encode($this->settings));
    }

}
