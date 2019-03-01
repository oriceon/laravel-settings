<?php

use Oriceon\Settings\Facades\Settings;

if ( ! function_exists('settings'))
{
	/**
	 * @param      $key
	 * @param null $default
	 *
	 * @return mixed|null
	 */
	function settings($key, $default = null)
	{
		return Settings::get($key, $default);
	}
}

if ( ! function_exists('multi_key_exists'))
{
    function multi_key_exists($keys, $arr)
    {
        if (is_array($arr))
        {
            foreach ($keys as $key)
            {
                if ( ! array_key_exists($key, $arr))
                {
                    return false;
                }

                $arr = &$arr[$key];
            }

            return true;
        }

        return false;
    }
}

if ( ! function_exists('set_nested_array_value'))
{
    /**
     * Sets a value in a nested array based on path
     *
     * @param array $array The array to modify
     * @param string $path The path in the array
     * @param mixed $value The value to set
     * @param string $delimiter The separator for the path
     *
     * @return The previous value
     */
    function set_nested_array_value(&$array, $path, $value = null, $delimiter = '.')
    {
        if (is_array($path))
        {
            array_shift($path);
            $pathParts = $path;
        }
        else
        {
            $pathParts = explode($delimiter, $path);
        }

        $current = &$array;
        foreach ($pathParts as $key)
        {
            $current = &$current[$key];
        }

        $backup  = $current;
        $current = $value;

        return $backup;
    }
}

if ( ! function_exists('build_array'))
{
    function build_array(array $keys, $value)
    {
        $return = [];

        $index = array_shift($keys);
        if ( ! isset($keys[0]))
        {
            $return[$index] = $value;
        }
        else
        {
            $return[$index] = build_array($keys, $value);
        }

        return $return;
    }
}

if ( ! function_exists('preg_key_exists'))
{
    function preg_key_exists($default_settings_key, array $settings)
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

if ( ! function_exists('key_represents_an_array'))
{
    function key_represents_an_array($key, $default_settings)
    {
        $split_key = preg_split('/\.([0-9]+)\.?/i', $key);

        // key represents an array or a multi array ?
        if (count($split_key) > 0)
        {
            // but that array still exists in default settings ?
            if (preg_key_exists($split_key[0], $default_settings))
            {
                return true;
            }
        }

        return false;
    }
}