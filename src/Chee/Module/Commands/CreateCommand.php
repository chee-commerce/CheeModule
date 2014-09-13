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

        if (!empty($name))
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

        $this->app['files']->makeDirectory($modulePath, 0755);

        $routes = '<?php'.PHP_EOL;
        $this->app['files']->put($modulePath.'/routes.php', $routes);

        $this->app['files']->put($modulePath.'/module.json', $this->app['files']->get(__DIR__.'/dev-module.json.txt'));
        $this->app['files']->put($modulePath.'/'.$name.'.php', $this->app['files']->get(__DIR__.'/dev-module.php.txt'));
        $this->app['files']->put($modulePath.'/'.$name.'ServiceProvider.php', $this->app['files']->get(__DIR__.'/dev-provider.php.txt'));

        $this->app['files']->makeDirectory($modulePath . '/assets', 0755);
		$this->app['files']->makeDirectory($modulePath . '/config', 0755);
		$this->app['files']->makeDirectory($modulePath . '/controllers', 0755);
		$this->app['files']->makeDirectory($modulePath . '/lang', 0755);
		$this->app['files']->makeDirectory($modulePath . '/models', 0755);
		$this->app['files']->makeDirectory($modulePath . '/migrations', 0755);
		$this->app['files']->makeDirectory($modulePath . '/views', 0755);

        $this->info('module '.$name.' generated successfully in '.$modulePath.'.');

        $model = new ModuleModel;
        $model->name = $name;
        if ($this->confirm('Enable this module? [no|yes]', false))
        {
            $model->status = 1;
        }
        $model->save();
        $this->error('Don\'t forget to correct class name and namespaces in every file have.');
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
