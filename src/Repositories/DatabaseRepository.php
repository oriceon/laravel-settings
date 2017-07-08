<?php

namespace Oriceon\Settings\Repositories;

use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Facades\Config;
use Oriceon\Settings\Utils\Utils;

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
        $this->database = $database;
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
                $newValue = Utils::build_array($newKeyExp, $value);
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
            Utils::set_nested_array_value($setting_value, $keyExp, $value);

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

            return Utils::multi_key_exists($keyExp, $setting_value);
        }

        return false;
    }

    /**
     * Remove a setting
     *
     * @param  string $key
     *
     * @return void
     */
    public function forget($key)
    {
        $keyExp = explode('.', $key);


        $query = $this->database
            ->table($this->config['db_table'])
            ->where('setting_key', $keyExp[0]);

        $row = $query->first(['setting_value']);

        if ( ! is_null($row))
        {
            if (count($keyExp) > 1)
            {
                $setting_value = json_decode($row->setting_value, true);

                Utils::array_unset($setting_value, $keyExp);

                $query->update([
                    'setting_value' => count($setting_value) ? json_encode($setting_value) : null
                ]);
            }
            else
            {
                $query->delete();
            }
        }


        $this->cache->forget($key);
    }

    /**
     * Clean unused settings
     *
     * @param $params
     */
    public function clean($params = [])
    {
        if ( ! empty($this->config['primary_config_file']))
        {
            $default_settings = $this->array_dot(Config::get($this->config['primary_config_file']), true);
            $settings         = $this->array_dot($this->getAll(false));

            if (array_key_exists('flush', $params) && $params['flush'] == true)
            {
                $this->flush();

                $settings = [];

                $this->_update($default_settings, $settings);
            }
            else
            {
                // clean unused settings
                foreach ($settings as $key => $value)
                {
                    if ( ! array_key_exists($key, $default_settings))
                    {
                        if ( ! $this->expect_array_as_a_value($key, $default_settings))
                        {
                            $this->forget($key);
                        }
                    }
                }


                $this->_update($default_settings, $settings);
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
            $value         = Utils::set_nested_array_value($setting_value, $keyExp);

            return $this->cache->set($key, $value);
        }

        return null;
    }

    /**
     * @param $default_settings
     * @param $settings
     */
    private function _update($default_settings, $settings)
    {
        // update with new settings
        foreach ($default_settings as $key => $value)
        {
            if ( ! $this->preg_key_exists($key, $settings))
            {
                $this->set($key, $value);
            }
        }
    }

    private function array_dot(array $array, $default_settings = false)
    {
        $newArray = [];

        $dots = array_dot($array);
        foreach ($dots as $key => $value)
        {
            $expKey  = explode('.', $key);
            $lastKey = array_last($expKey);

            if (is_numeric($lastKey))
            {
                $newKey   = implode('.', array_slice($expKey, 0, -1));
                $newValue = [];

                if ($default_settings && ! empty($this->config['primary_config_file']))
                {
                    $newValue = Config::get($this->config['primary_config_file'] . '.' . $newKey, $newValue);
                }

                $newArray[$newKey] = $newValue;
            }
            else
            {
                $newArray[$key] = $value;
            }
        }

        return $newArray;
    }

    private function expect_array_as_a_value($key, $default_settings)
    {
        $exp     = explode('.', $key);
        $cnt_exp = count($exp) - 1;

        // if found prev key
        if (isset($exp[$cnt_exp - 1]))
        {
            // unset last key
            unset($exp[$cnt_exp]);

            // reconstruct dotted keys
            $key = implode('.', $exp);

            // if found path in default settings
            // and path expect value as a array
            // then we should NOT forget path from settings
            if (array_key_exists($key, $default_settings) && is_array($default_settings[$key]))
            {
                return true;
            }
        }

        return false;
    }

    private function preg_key_exists($default_settings_key, array $settings)
    {
        $found = false;

        foreach ($settings as $key => $value)
        {
            if (preg_match('/' . $default_settings_key . '/i', $key))
            {
                $found = true;
            }
        }

        return $found;
    }

}
