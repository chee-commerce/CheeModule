<?php namespace Chee\Module\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Illuminate\Foundation\Application;

class ListCommand extends AbstractCommand
{
    /**
     * Name of the command
     * @var string
     */
    protected $name = 'CheeModule';

    /**
     * Command description
     * @var string
     */
    protected $description = 'List of CheeModule commands';

    /**
     * Echo a list of commands in CheeModule
     */
    public function fire()
    {
        $this->info('CheeModule Commands:');
        $this->info('-----------------------------------------------------------------------------------------------------------------------------');
        $this->info('| CheeModule::create      | Create a new module for development. | eg: php artisan CheeModule::create name=moduleName');
        $this->info('-----------------------------------------------------------------------------------------------------------------------------');
        $this->info('| CheeModule::buildAssets | Move assets directory to public.     | eg: php artisan CheeModule::buildAssets name=moduleName');
        $this->info('-----------------------------------------------------------------------------------------------------------------------------');
        $this->info('');
    }

    /**
     * Get the console command arguments.
     * @return array
     */
    protected function getArguments()
    {
        return array(

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
