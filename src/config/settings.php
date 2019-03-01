<?php

return [
	/*
	|--------------------------------------------------------------------------
	| Cache Filename
	|--------------------------------------------------------------------------
	|
	| Cache configuration path
	|
	*/
	'cache_file' => storage_path('settings.json'),

    /*
    |--------------------------------------------------------------------------
    | Database connection name
    |--------------------------------------------------------------------------
    |
    */
    'db_connection' => '',

	/*
	|--------------------------------------------------------------------------
	| Table name to store settings
	|--------------------------------------------------------------------------
	|
	*/
	'db_table' => 'settings__lists',

	/*
	|--------------------------------------------------------------------------
	| Fallback setting
	|--------------------------------------------------------------------------
	|
	| Return Laravel config if the value with particular key is not found in cache or DB.
	| It will work if default value in laravel setting is not set, and this value is set to true
	|
	*/
	'fallback' => true,

    /*
    |--------------------------------------------------------------------------
    | Name of your desired application config file
    |--------------------------------------------------------------------------
    |
    */
    'primary_config_file' => '',
];
