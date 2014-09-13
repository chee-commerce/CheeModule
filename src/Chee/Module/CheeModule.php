<?php namespace Chee\Module;

use Illuminate\Foundation\Application;
use Illuminate\Config\Repository;
use Illuminate\Filesystem\Filesystem;

class CheeModule
{

    protected $app;

    protected $config;

    protected $modulesPath;

    protected $modules = array();

    protected $model;

    protected $files;

    public function __construct(Application $app, Repository $config, Filesystem $files)
    {
        $this->app = $app;
        $this->config = $config;
        $this->files = $files;

        $this->modulesPath = $this->config->get('module::modules_path');
    }

    /**
     * get all module from database and initialize
     */
    public function start()
    {
        $modules = ModuleModel::all();
        foreach($modules as $module)
        {
            if ($module->status)
            {
                $path = $this->getModuleDirectory($module->name);
                if ($path)
                {
                    if ($this->checkModuleName($path . '/module.json', $module->name))
                    {
                        $name = $module->name;
                        $this->modules[$name] = new Module($this->app, $module->name, $path);
                    }
                }
            }
        }
    }

    /**
     * Register modules with Moduel class
     */
    public function register()
    {
        foreach ($this->modules as $module)
        {
            $module->register();
        }
        $modules = ModuleModel::where('enable', 1)->get();
        foreach ($modules as $module)
        {
            $this->app['events']->fire('modules.enable.'.$module->name, null);
            $module->enable = 0;
            $module->save();
        }
    }

    /**
     * Enable module to load
     * @param name string
     * @return boolean
     */
    public function enable($name)
    {
        $module = $this->findOrFalse('name', $name);
        if ($module)
        {
            if(!$module->status)
            {
                $module->status = 1;
                $module->enable = 1;
                $module->save();
                return true;
            }
            /* This module has alreay enable*/
            return false;
        }
        /* This module not found in database*/
        return false;
    }


    /**
     * Disable module
     * @param name string
     * @return boolean
     */
    public function disable($name)
    {
        $module = $this->findOrFalse('name', $name);
        if ($module)
        {
            if($module->status)
            {
                $module->status = 0;
                $module->save();
                $this->app['events']->fire('modules.disable.'.$name, null);
                return true;
            }
            /* This module has already disable*/
            return false;
        }
        /* This module not found in database*/
        return false;
    }

    public function buildAssets($name)
    {
        $module = $this->getModuleDirectory($name);
        if ($module)
        {
            $module .= '/assets';
            if ($this->files->exists($module))
            {
                if (!@$this->files->copyDirectory($module, $this->getAssetDirectory($name)))
                {
                    return false; //Can not make directory
                }
            }
        }
    }

    /**
     * Uninstall module
     * @param $name string
     * @return boolean
     */
    public function uninstall($name)
    {
        $module = $this->findOrFalse('name', $name);
        if ($module)
        {
            $this->app['events']->fire('modules.uninstall.'.$name, null);
            $module->delete();
            $modulePath = $this->getModuleDirectory($name);
            $this->files->deleteDirectory($modulePath);
            $this->files->deleteDirectory($this->getAssetDirectory($name));
            return true;
        }
        return false;
    }

    /**
     * Find one record from model
     * @param $field string
     * @param $name string
     * @return object|false
     */
    public function findOrFalse($field, $name) {
        $module = ModuleModel::where($field, $name)->first();
        return !is_null($module) ? $module : false;
    }

    /**
     * Get path of speciic module
     * @param $name string name of module
     * @return string
     */
    public function getModuleDirectory($name)
    {
        if ($this->app['files']->exists(base_path($this->modulesPath.$name)))
        {
            return base_path($this->modulesPath.$name);
        }
        else
        {
            return false;
        }
    }

    public function getAssetDirectory($name)
    {
        return public_path().$this->config->get('module::assets').'/'.$name;
    }

    /**
     * Check module name correct
     * @param $path string path of the module
     * @param $nameModule string name of the module
     * @return true|false
     */
    protected function checkModuleName($path, $nameModule)
    {
        $definition = json_decode($this->files->get($path), true);
        if (isset($definition['name']))
        {
            if ($definition['name'] == $nameModule)
            {
                return true;
            }
        }
        return false;
    }
}
