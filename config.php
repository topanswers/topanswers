<?php

/**
 * Gets the value of an environment variable.
 * Returns the default if the env variable does not exist.
 *
 * @param  string  $key
 * @param  mixed   $default
 * @return mixed
 */
function env($key, $default = null)
{
    $value = getenv($key);
    if ($value === false) {
        return $default;
    }
    return $value;
}

function config($key)
{
    static $config = NULL;
    
    if (!isset($config)) {
        $config = [
            "DB_HOST" => env("DB_HOST", ''),
            "DEV_HOST" => env("DEV_HOST", '127.0.0.1')
        ];
    }
    
    return $config[$key];
}
