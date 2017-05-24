<?php

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

if ( ! function_exists('array_unset'))
{
    function array_unset(&$array, $path, $delimiter = '.')
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

        $i = 0;
        while ($i < count($pathParts) - 1)
        {
            $piece = $pathParts[$i];
            if ( ! is_array($array) || ! array_key_exists($piece, $array))
            {
                return null;
            }

            $array = &$array[$piece];

            ++$i;
        }

        $piece = end($pathParts);

        unset($array[$piece]);

        return $array;
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