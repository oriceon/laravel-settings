[![Build Status](https://travis-ci.org/oriceon/laravel-settings.svg?branch=master)](https://travis-ci.org/oriceon/laravel-settings)
[![Latest Stable Version](https://poser.pugx.org/oriceon/laravel-settings/v/stable.svg)](https://packagist.org/packages/oriceon/laravel-settings)
[![Total Downloads](https://poser.pugx.org/oriceon/laravel-settings/downloads.svg)](https://packagist.org/packages/oriceon/laravel-settings)
[![License](https://poser.pugx.org/oriceon/laravel-settings/license.svg)](https://packagist.org/packages/oriceon/laravel-settings)

# Laravel-Settings

Laravel 5.4.x persistent settings using JSON cache file

# Install process

1. Require this package with composer :

    `composer require oriceon/laravel-settings`

2. Register the ServiceProvider to the `providers` array in `config/app.php`

    `Oriceon\Settings\SettingsServiceProvider::class,`

3. Add an alias for the facade to `aliases` array in  your `config/app.php`

    `'Settings'  => Oriceon\Settings\Facades\Settings::class,`

4. Publish the config and migration files now:

    `php artisan vendor:publish --provider="Oriceon\Settings\SettingsServiceProvider" --force`

Change `config/settings.php` according to your needs.

Create the `settings` table.

    php artisan migrate

# How to Use it?

### Set a value

    Settings::set('key', 'value');
    Settings::set('key1.key2', 'value');

### Get a value

    $value = Settings::get('key');
    $value = Settings::get('key1.key2');

### Get a value with Default Value.

    $value = Settings::get('key', 'Default Value');

### Get all settings

    $values = Settings::getAll();

> Note: default, all settings are loaded from cache. If you want to load them from database with this method just call with a parameter:
 
    $values = Settings::getAll($cache = false);

> Note: If key is not found (null) in cache or settings table, it will return default value

### Has a key

    $value = Settings::has('key');
    $value = Settings::has('key1.key2');

### Forget a value

    Settings::forget('key');
    Settings::forget('key1.key2');

### Clean and update settings from config file

    Settings::clean();
    Settings::clean(['flush' => true]);
> Note: using flush parameter, will forget all values and reload settings from config file

### Forget all values

    Settings::flush();

## Fallback to Laravel config

    // Change your config/settings.php
    'fallback' => true

### Example

    /*
     * If the value with key => mail.host is not found in cache or DB of Larave Settings
     * it will return same value as config::get('mail.host');
     */
    Settings::get('mail.host');
    
## Primary config file

    // Change your config/settings.php
    'primary_config_file' => 'filename'

> Note: If you want to have a site config file with all your default settings, create a file in config/ with desired name and create your config file as you want.
 After that, you may no longer need to enter the file name in setting calls.
 >> Instead Settings::get('filename.setting_key') you will just call for Settings::get('setting_key')

> Note: It will work if default value in laravel setting is not set

### Get a value via an helper

    $value = settings('key');
    $value = settings('key', 'default value');

# Credits to main author

Fwork package : [ABENEVAUT/laravel-settings](https://github.com/ABENEVAUT/laravel-settings)

Original package : [efriandika/laravel-settings](https://github.com/efriandika/laravel-settings)
