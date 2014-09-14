<?php namespace Chee\Module;

use Illuminate\Foundation\Application;
use Illuminate\Config\Repository;
use Illuminate\Filesystem\Filesystem;

/**
 * CheeModule for manage module
 * @author Chee
 */
class CheeModule
{

    /**
	 * IoC
	 * @var Illuminate\Foundation\Application
	 */
    protected $app;

    /**
     * Config
     * @var Illuminate\Config\Repository
     */
    protected $config;

    /**
     * Path of modules
     * @var string
     */
    protected $modulesPath;

    /**
     * Array of Module
     * @var Illuminate\Config\Repository
     */
    protected $modules = array();

    /**
     * Files
     * @var Illuminate\Filesystem\Filesystem
     */
    protected $files;

    /**
     * Initialize class
     */
    public function __construct(Application $app, Repository $config, Filesystem $files)
    {
        $this->app = $app;
        $this->config = $config;
        $this->files = $files;

        $this->modulesPath = $this->config->get('module::modules_path');
    }

    /**
     * Get all module from database and initialize
     */
    public function start()
    {
        $modules = ModuleModel::all();
        foreach($modules as $module)
        {
            if ($module->installed && $module->status)
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
            if(!$module->status && $module->installed)
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
            if($module->status && $module->installed)
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

    /**
     * Reset configuration module
     * @param name string
     * @return boolean
     */
    public function reset($name)
    {
        $module = $this->findOrFalse('name', $name);
        if ($module)
        {
            if($module->status && $module->installed)
            {
                $this->app['events']->fire('modules.reset.'.$name, null);
                $module->status = 0;
                $module->save();
                $this->app['events']->fire('modules.disable.'.$name, null);
                return true;
            }
            /* This module is disable or uninstalled and can not be reset*/
            return false;
        }
        return false;
    }

    /**
     * Get all list modules
     * @return object
     */
    public function getListAllModules()
    {
        $modulesModel = ModuleModel::all();
        return $this->getListModules($modulesModel);
    }

    /**
     * Get customize list modules
     * @param $status boolean
     * @param $installed boolean
     * @return object
     */
    public function getListCustomModules($status = 1, $installed = 1)
    {
        $modulesModel = ModuleModel::where('status', $status)->where('installed', $installed)->get();
        return $this->getListModules($modulesModel);
    }

    /**
     * Get list modules
     * @param $modulesModel model
     * @return array
     */
    public function getListModules($modulesModel)
    {
        $modules = array();
        foreach ($modulesModel as $module)
        {
            $modules[$module->name]['name'] = $this->def($module->name, 'name');
            $modules[$module->name]['icon'] = $this->config->get('module::assets').'/'.$module->name.'/'.$this->def($module->name, 'icon');
            $modules[$module->name]['description'] = $this->def($module->name, 'description');
            $modules[$module->name]['author'] = $this->def($module->name, 'author');
            $modules[$module->name]['website'] = $this->def($module->name, 'website');
            $modules[$module->name]['version'] = $this->def($module->name, 'version');
        }
        return $modules;
    }

    /**
     * Move contents assets module to public directory
     * @param $name string
     * @return boolean
     */
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
            return true;
        }
        return false;
    }

    public function install($name)
    {

    }

    /**
     * Uninstall module and remove assets files but keep module files for install again
     * @param $name string
     * @return boolean
     */
    public function uninstall($name)
    {
        $module = $this->findOrFalse('name', $name);
        if ($module)
        {
            if ($module->installed)
            {
                if ($module->status)
                {
                    $this->app['events']->fire('modules.disable.'.$name, null);
                    $module->status = 0;
                }
                $this->app['events']->fire('modules.uninstall.'.$name, null);
                $module->installed = 0;
                $module->save();
                $this->files->deleteDirectory($this->getAssetDirectory($name));
                return true;
            }
        }
        return false;
    }

    /**
     * Delete module and remove assets and module files
     * @param $name string
     * @return boolean
     */
    public function delete($name)
    {
        $module = $this->findOrFalse('name', $name);
        if ($module)
        {
            $this->app['events']->fire('modules.disable.'.$name, null);
            $this->app['events']->fire('modules.uninstall.'.$name, null);
            $this->app['events']->fire('modules.delete.'.$name, null);
            $module->delete();
            $this->files->deleteDirectory($this->getAssetDirectory($name));
            $this->files->deleteDirectory($this->getModuleDirectory($name););
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

    /**
     * Get assets path of speciic module
     * @param $name string name of module
     * @return string
     */
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

    /**
     * Get module.json data of module
     * @param $key string|null key of array
     * @return array|string
     */
    protected function def($moduleName, $key = null)
    {
        $definition = json_decode($this->app['files']->get($this->getModuleDirectory($moduleName) . '/module.json'), true);
        if ($key) return isset($definition[$key]) ? $definition[$key] : null;
        else return $definition;
    }
}
