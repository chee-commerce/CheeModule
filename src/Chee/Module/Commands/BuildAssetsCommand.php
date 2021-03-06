<?php namespace Chee\Module\Commands;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Illuminate\Foundation\Application;
use Chee\Module\Models\ModuleModel;
use Illuminate\Console\Command;
use Chee\Module\CheeModule;

class BuildAssetsCommand extends AbstractCommand
{
    /**
     * Name of the command
     * @var string
     */
    protected $name = 'CheeModule:buildAssets';

    /**
     * Command description
     * @var string
     */
    protected $description = 'Move assets directory to public';

    public function fire()
    {
        $name = studly_case(substr($this->argument('name'), strpos($this->argument('name'), '=') + 1));

        if (empty($name))
        {
            $this->error('Please write module name');
            exit;
        }

        $build = $this->app['chee-module']->buildAssets($name);
        if ($build)
        {
            $this->info('moved assets '.$name.' module in '.public_path().$this->app['config']->get('module::assets').'/'.$name.' successfully.');
        }
        else
        {
            $this->error('Error on move assets '.$name.' module in'.public_path().'/'.$name);
        }
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
