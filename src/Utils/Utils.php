<?php

namespace Oriceon\Settings\Utils;

class Utils {

    public static function multi_key_exists($keys, $arr)
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
    public static function set_nested_array_value(&$array, $path, $value = null, $delimiter = '.')
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

    public static function array_unset(&$array, $path, $delimiter = '.')
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

    public static function build_array(array $keys, $value)
    {
        $return = [];

        $index = array_shift($keys);
        if ( ! isset($keys[0]))
        {
            $return[$index] = $value;
        }
        else
        {
            $return[$index] = self::build_array($keys, $value);
        }

        return $return;
    }
}