<?php

namespace Oriceon\Settings\Repositories;

use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Config;

class DatabaseRepository
{
    /**
     * Registry config
     *
     * @var array
     */
    protected $config;

    /**
     * Database manager instance
     *
     * @var \Illuminate\Database\DatabaseManager
     */
    protected $database;

    /**
     * Cache
     *
     * @var CacheRepository
     */
    protected $cache;

    /**
     * Constructor
     *
     * @param DatabaseManager $database
     * @param CacheRepository $cache
     * @param array $config
     */
    public function __construct(
        DatabaseManager $database,
        CacheRepository $cache,
        $config = []
    )
    {
        $this->database = $database->connection($config['db_connection']);
        $this->config   = $config;
        $this->cache    = $cache;
    }

    /**
     * Store value into registry
     *
     * @param  string $key
     * @param  mixed  $value
     *
     * @return mixed
     */
    public function set($key, $value)
    {
        $keyExp = explode('.', $key);

        $row = $this->database
            ->table($this->config['db_table'])
            ->where('setting_key', $keyExp[0])
            ->first(['setting_value']);

        if (is_null($row))
        {
            $newKeyExp = array_slice($keyExp, 1);

            $newValue = $value;

            // if want to set a value directly from a dot keys
            if (count($newKeyExp) > 0)
            {
                // we should compose a new array with keys from setter key
                $newValue = build_array($newKeyExp, $value);
            }


            $this->database->table($this->config['db_table'])
                ->insert([
                    'setting_key'   => $keyExp[0],
                    'setting_value' => ( ! empty($newValue) ? json_encode($newValue) : null),
                ]);
        }
        else
        {
            $setting_value = json_decode($row->setting_value, true);
            set_nested_array_value($setting_value, $keyExp, $value);

            $this->database->table($this->config['db_table'])
                ->where('setting_key', $keyExp[0])
                ->update([
                    'setting_value' => json_encode($setting_value)
                ]);
        }


        $this->cache->set($key, $value);

        return $value;
    }

    /**
     * Gets a value
     *
     * @param  string $key
     * @param  string $default
     *
     * @return mixed
     */
    public function get($key, $default = null)
    {
        $value = $this->fetch($key);

        if ( ! is_null($value))
        {
            return $value;
        }

        if ($default != null)
        {
            return $default;
        }

        if ($this->config['fallback'])
        {
            if ( ! is_null($this->config['primary_config_file']))
            {
                $key2 = $this->config['primary_config_file'] . '.' . $key;
                if (Config::has($key2))
                {
                    return Config::get($key2);
                }
            }

            return Config::get($key, null);
        }

        return $default;
    }

    /**
     * Fetch all values
     *
     * @param $cache
     *
     * @return mixed
     */
    public function getAll($cache = true)
    {
        if ($cache)
        {
            return $this->cache->getAll();
        }


        $all = [];

        $rows = $this->database->table($this->config['db_table'])->get();
        foreach ($rows as $row)
        {
            $value = json_decode($row->setting_value, true);

            $all[$row->setting_key] = $value;
        }

        return $all;
    }

    /**
     * Checks if setting exists
     *
     * @param $key
     *
     * @return bool
     */
    public function has($key)
    {
        $keyExp = explode('.', $key);

        if ($this->cache->has($key))
        {
            return true;
        }

        $row = $this->database
            ->table($this->config['db_table'])
            ->where('setting_key', $keyExp[0])
            ->first(['setting_value']);

        if ( ! is_null($row))
        {
            $setting_value = json_decode($row->setting_value, true);

            array_shift($keyExp);

            return multi_key_exists($keyExp, $setting_value);
        }

        return false;
    }

    /**
     * Remove a settings from database and cache file
     *
     * @param  string $key
     *
     * @return void
     */
    public function forget($key)
    {
        $keyExp = explode('.', $key);

        // get settings value by first key name
        $query = $this->database
            ->table($this->config['db_table'])
            ->where('setting_key', $keyExp[0]);

        $row = $query->first(['setting_value']);

        if ( ! is_null($row))
        {
            if (count($keyExp) > 1)
            {
                // if found more keys, then forget last key from array
                $setting_value = json_decode($row->setting_value, true);

                unset($keyExp[0]);
                $newKey = implode('.', $keyExp);

                Arr::forget($setting_value, $newKey);


                if (count($setting_value) > 0)
                {
                    // if we still have settings, update settings row
                    $query->update(['setting_value' => json_encode($setting_value)]);
                }
                else
                {
                    // if settings value remain blank, delete settings row
                    $query->delete();
                }
            }
            else
            {
                // if found only one key, delete settings row
                $query->delete();
            }
        }

        $this->cache->forget($key);
    }

    /**
     * Cleans settings that are no longer used in primary config file
     *
     * @param $params
     */
    public function clean($params = [])
    {
        if ( ! empty($this->config['primary_config_file']))
        {
            $default_settings = Arr::dot(Config::get($this->config['primary_config_file']));
            $saved_settings   = Arr::dot($this->getAll($cache = false));

            if (array_key_exists('flush', $params) && $params['flush'] == true)
            {
                $this->flush();

                $saved_settings = [];
            }
            else
            {
                foreach ($saved_settings as $key => $value)
                {
                    if ( ! array_key_exists($key, $default_settings))
                    {
                        if ( ! key_represents_an_array($key, $default_settings))
                        {
                            $this->forget($key);
                        }
                    }
                }
            }


            // update with new settings
            foreach ($default_settings as $key => $value)
            {
                if ( ! preg_key_exists($key, $saved_settings))
                {
                    // check if key does not represents an array and exists in saved settings
                    if ( ! key_represents_an_array($key, $saved_settings))
                    {
                        $this->set($key, $value);
                    }
                }
            }
        }
    }

    /**
     * Remove all settings
     *
     * @return bool
     */
    public function flush()
    {
        $this->cache->flush();

        return $this->database->table($this->config['db_table'])->delete();
    }


    /**
     * @param $key
     *
     * @return mixed|null
     */
    private function fetch($key)
    {
        $keyExp = explode('.', $key);

        if ($this->cache->has($key))
        {
            return $this->cache->get($key);
        }


        $row = $this->database
            ->table($this->config['db_table'])
            ->where('setting_key', $keyExp[0])
            ->first(['setting_value']);

        if ( ! is_null($row))
        {
            $setting_value = json_decode($row->setting_value, true);
            $value         = set_nested_array_value($setting_value, $keyExp);

            return $this->cache->set($key, $value);
        }

        return null;
    }

}