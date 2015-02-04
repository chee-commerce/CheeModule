<?php namespace Chee\Module\Commands;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Illuminate\Foundation\Application;
use Illuminate\Console\Command;

class UninstallEventCommand extends AbstractCommand
{
    /**
     * Name of the command
     * @var string
     */
    protected $name = 'CheeModule:uninstallEvent';

    /**
     * Command description
     * @var string
     */
    protected $description = 'Run uninstall event for a module';

    /**
     * Echo a list of commands in CheeModule
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

        $this->app['events']->fire('modules.uninstall.'.$name, null);
        $this->error("This command just call event, do not any more.");
        $this->info("Uninstall event for $name module called.");
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
