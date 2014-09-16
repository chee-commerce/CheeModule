<?php namespace Chee\Module\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Illuminate\Foundation\Application;
use Chee\Module\ModuleModel;

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
	protected $description = 'Create a new dev module for CheeModule manager.';

    public function fire()
    {
        $name = studly_case(substr($this->argument('name'), strpos($this->argument('name'), '=') + 1));
        $modulePath = $this->modulesPath.$name;

        if (empty($name))
        {
            $this->error('Please write module name');
            exit;
        }

        if (in_array($name, $this->getModulesDirectories()))
        {
            $this->error('Module '.$name.' created before [directory].');
            exit;
        }

        $module = ModuleModel::where('name', $name)->first();
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
        $this->info('Please wait...');

        $this->app['files']->makeDirectory($modulePath, 0775);

        $moduleRoutes = $modulePath.'/routes.php';
        $moduleJSON = $modulePath.'/module.json';
        $moduleFile = $modulePath.'/'.$name.'.php';
        $moduleProvider = $modulePath.'/'.$name.'ServiceProvider.php';

        $this->app['files']->put($moduleRoutes, $this->app['files']->get(__DIR__.'/dev-routes.php.txt'));
        $this->app['files']->put($moduleJSON, $this->app['files']->get(__DIR__.'/dev-module.json.txt'));
        $this->app['files']->put($moduleFile, $this->app['files']->get(__DIR__.'/dev-module.php.txt'));
        $this->app['files']->put($moduleProvider, $this->app['files']->get(__DIR__.'/dev-provider.php.txt'));

        $this->app['files']->makeDirectory($modulePath . '/assets', 0775);
		$this->app['files']->makeDirectory($modulePath . '/config', 0775);
		$this->app['files']->makeDirectory($modulePath . '/controllers', 0775);
		$this->app['files']->makeDirectory($modulePath . '/lang', 0775);
		$this->app['files']->makeDirectory($modulePath . '/models', 0775);
		$this->app['files']->makeDirectory($modulePath . '/migrations', 0775);
		$this->app['files']->makeDirectory($modulePath . '/views', 0775);

        file_put_contents($moduleRoutes, str_replace('module-name', $name, file_get_contents($moduleRoutes)));
        file_put_contents($moduleJSON, str_replace('module-name', $name, file_get_contents($moduleJSON)));
        file_put_contents($moduleFile, str_replace('module-name', $name, file_get_contents($moduleFile)));
        file_put_contents($moduleProvider, str_replace('module-name', $name, file_get_contents($moduleProvider)));

        $this->app['files']->copy(__DIR__.'/icon.png', $modulePath.'/assets/icon.png');

        $this->info('module '.$name.' generated successfully in '.$modulePath.'.');

        $model = new ModuleModel;
        $model->name = $name;
        $model->status = 0;
        $model->installed = 0;
        $model->version = '0.0.1';
        $model->save();
        $this->error('This module has been disabled and uninstalled.');
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
