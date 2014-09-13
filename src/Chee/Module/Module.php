<?php namespace Chee\Module;

use Illuminate\Foundation\Application;
use Illuminate\Foundation\AliasLoader;
use Illuminate\Support\ServiceProvider;

/**
 * Register service provider and some files determined in config.php of CheeModule and module.json of modules.
 */
class Module extends ServiceProvider
{

    /**
     * @var string $name Name of the module
     */
    protected $name;

    /**
     * @var string $path Path of the module
     */
    protected $path;

    /**
     * @var array $definition module.json data of module
     */
    protected $definition;

    /**
     * Initialize a module
     * @param Application $app
     * @param string $name name of the module
     * @param string $path path of the module
     */
    public function __construct(Application $app, $name, $path)
    {
        $this->app = $app;
        $this->name = $name;
        $this->path = $path;

        $this->definition = json_decode($app['files']->get($path . '/module.json'), true);
    }

    /**
     * Register module
     */
    public function register()
    {
        $this->package($this->path, $this->name, $this->path);
        $this->registerProviders();
        $this->includes();
    }

    /**
     * Include some files determined in config.php of CheeModule and module.json of modules
     */
    protected function includes()
    {
        $moduleInclude = (array) $this->def('include');
        $globalInclude = $this->app['config']->get('module::include');
        $specificInclude = array($this->name.'.php');
        $include = array_merge($moduleInclude, $specificInclude, $globalInclude);
        foreach ($include as $file)
		{
			$path = $this->path.'/'.$file;
			if ($this->app['files']->exists($path)) require $path;
		}
    }

    /**
     * Register service providers of module
     */
    protected function registerProviders()
    {
        $providers = $this->def('provider');

        if ($providers)
        {
            if (is_array($providers))
            {
                foreach ($providers as $provider)
                {
                    $this->app->register($instance = new $provider($this->app));
                    $this->app['events']->fire('modules.install.' . $this->name);
                }
            }
            else
            {
                $this->app->register($instence = new $providers($this->app));
            }
        }
    }

    /**
     * Get module.json data of module
     * @param $key string|null key of array
     * @return array|string
     */
    protected function def($key = null)
    {
        if ($key) return isset($this->definition[$key]) ? $this->definition[$key] : null;
        else return $this->definition;
    }
}
