<?php namespace Siteshop\Conditions;

use Illuminate\Support\ServiceProvider;

class ConditionsServiceProvider extends ServiceProvider {

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
        $this->package('siteshop/conditions');
    }

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
		$this->app['condition'] = $this->app->share(function($app){
			return new Condition;
		});
	}

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides()
	{
		return array('condition');
	}

}
