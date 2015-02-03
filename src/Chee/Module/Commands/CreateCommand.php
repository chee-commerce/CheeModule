<?php namespace Chee\Module\Commands;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Illuminate\Foundation\Application;
use Chee\Module\Models\ModuleModel;
use Illuminate\Console\Command;

class CreateCommand extends AbstractCommand
{
    /**
	 * Name of the command
	 * @var string
	 */
    protected $name = 'CheeModule:create';

    /**
	 * Command description
	 * @var string
	 */
	protected $description = 'Create a new module for development';


    /**
     * Create module
     */
    public function fire()
    {
        $name = studly_case(substr($this->argument('name'), strpos($this->argument('name'), '=') + 1));

        $modulePath = $this->modulesPath.'/'.$name;

        if (empty($name))
        {
            $this->error('Please write correct module name');
            exit;
        }

        if (in_array($name, $this->getModulesDirectories()))
        {
            $this->error('Module '.$name.' created before [directory].');
            exit;
        }

        $module = ModuleModel::where('module_name', $name)->first();
        if ($module)
        {
            $this->error('Module '.$name.' created before [database].');
            exit;
        }

        if ($this->confirm('Check unique module name? [yes|no]', true))
        {
            $this->info('Please wait...');
        }

        $this->info('Generating module '.$name);

        $this->app['files']->makeDirectory($modulePath, 0775);

        $moduleRoutes = $modulePath.'/routes.php';
        $moduleJSON = $modulePath.'/module.json';
        $moduleFile = $modulePath.'/'.$name.'.php';
        $moduleSetup = $modulePath.'/Setup.php';
        $moduleProvider = $modulePath.'/'.$name.'ServiceProvider.php';

        $this->app['files']->put($moduleRoutes, $this->app['files']->get(__DIR__.'/new_module/routes.php'));
        $this->app['files']->put($moduleJSON, $this->app['files']->get(__DIR__.'/new_module/module.json'));
        $this->app['files']->put($moduleFile, $this->app['files']->get(__DIR__.'/new_module/module.php'));
        $this->app['files']->put($moduleSetup, $this->app['files']->get(__DIR__.'/new_module/setup.php'));
        $this->app['files']->put($moduleProvider, $this->app['files']->get(__DIR__.'/new_module/provider.php'));

        $this->app['files']->makeDirectory($modulePath . '/assets', 0775);
		$this->app['files']->makeDirectory($modulePath . '/config', 0775);
		$this->app['files']->makeDirectory($modulePath . '/lang', 0775);
		$this->app['files']->makeDirectory($modulePath . '/views', 0775);
		$this->app['files']->makeDirectory($modulePath . '/Models', 0775);
		$this->app['files']->makeDirectory($modulePath . '/Controllers', 0775);

        file_put_contents($moduleRoutes, str_replace('#moduleName', $name, file_get_contents($moduleRoutes)));
        file_put_contents($moduleJSON, str_replace('#moduleName', $name, file_get_contents($moduleJSON)));
        file_put_contents($moduleFile, str_replace('#moduleName', $name, file_get_contents($moduleFile)));
        file_put_contents($moduleSetup, str_replace('#moduleName', $name, file_get_contents($moduleSetup)));
        file_put_contents($moduleProvider, str_replace('#moduleName', $name, file_get_contents($moduleProvider)));

        $this->app['files']->copy(__DIR__.'/new_module/icon.png', $modulePath.'/assets/icon.png');

        $this->info('module '.$name.' generated successfully in '.$modulePath.'.');

        $model = new ModuleModel;
        $model->module_name = $name;
        $model->module_status = 0;
        $model->module_is_enabled = 0;
        $model->module_is_installed = 1;
        $model->module_version = '0.0.1';
        $model->save();
        $this->error('This module has been disabled.');
    }

    /**
	 * Get the console command arguments.
	 * @return array
	 */
	protected function getArguments()
	{
		return array(
            array('name', InputArgument::REQUIRED, 'the name of module.')
        );
	}

	/**
	 * Get the console command options.
	 * @return array
	 */
	protected function getOptions()
	{
		return array();
	}
}
