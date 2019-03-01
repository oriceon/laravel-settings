<?php

namespace Oriceon\Settings;

use Illuminate\Support\ServiceProvider;
use Oriceon\Settings\Repositories\CacheRepository;
use Oriceon\Settings\Repositories\DatabaseRepository;

class SettingsServiceProvider extends ServiceProvider
{
	/**
	 * Indicates if loading of the provider is deferred.
	 *
	 * @var bool
	 */
	protected $defer = false;

	/**
	 * Bootstrap the application events.
	 *
	 * @return void
	 */
	public function boot()
	{
		$this->publishes([
			__DIR__ . '/config/settings.php' => config_path('settings.php')
		]);

		$this->publishes([
			__DIR__ . '/database/migrations/' => base_path('/database/migrations')
		]);
	}

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
		$this->mergeConfigFrom(__DIR__ . '/config/settings.php', 'settings');

		$this->app->singleton('settings', function ($app)
		{
			$config = $app->config->get('settings', require __DIR__ . '/config/settings.php');

			return new DatabaseRepository(
				$app['db'],
				new CacheRepository($config['cache_file']),
				$config
			);
		});
	}

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides()
	{
		return ['settings'];
	}
}