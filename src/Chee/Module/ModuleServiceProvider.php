<?php namespace Chee\Module;

use Illuminate\Support\ServiceProvider;

class ModuleServiceProvider extends ServiceProvider
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
		$this->package('chee/module');

		$this->bootCommands();

		$this->app['chee-module']->start();

		$this->app['chee-module']->register();
	}

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
		$this->app['chee-module'] = $this->app->share(function($app)
		{
			return new CheeModule($app, $app['config'], $app['files']);
		});
	}

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides()
	{
		return array('module');
	}

	public function bootCommands()
	{
		$this->app['CheeModule'] = $this->app->share(function($app)
		{
			return new Commands\ListCommand($app);
		});

		$this->app['CheeModule.create'] = $this->app->share(function($app)
		{
			return new Commands\CreateCommand($app);
		});

		$this->app['CheeModule.buildAssets'] = $this->app->share(function($app)
		{
			return new Commands\BuildAssetsCommand($app);
		});

		$this->commands(array(
			'CheeModule',
			'CheeModule.create',
			'CheeModule.buildAssets',
		));
	}

}
