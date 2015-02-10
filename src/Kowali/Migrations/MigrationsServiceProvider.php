<?php namespace Kowali\Migrations;

use Illuminate\Support\ServiceProvider;

class MigrationsServiceProvider extends ServiceProvider {

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
        $this->commands('kowali.commands.contents-migrate');
        $this->commands('kowali.commands.contents-taxonomies');
	}

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
        $this->app->bindShared('kowali.commands.contents-migrate', function($app){
            return new Commands\ContentsMigrateCommand;
        });
        $this->app->bindShared('kowali.commands.contents-taxonomies', function($app){
            return new Commands\ContentsTaxonomiesCommand;
        });

    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['kowali.commands.contents-migrate', 'kowali.commands.contents-taxonomies'];
    }
}

