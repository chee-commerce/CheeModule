<?php namespace Chee\Module;

use Illuminate\Foundation\Application;
use Illuminate\Filesystem\Filesystem;
use Chee\Module\Models\ModuleModel;
use Illuminate\Support\MessageBag;
use Illuminate\Config\Repository;
use Chee\Version\Version;
use Chee\Pclzip\Pclzip;

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
    protected $path;

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
     * Keep errors
     * @var Illuminate\Support\MessageBag
     */
    protected $errors = array();

    /**
     * use CheeModule for?
     * @var string
     */
    protected $systemName;

    /**
     * Configuration file in every module
     * @var string
     */
    protected $configFile;

    /**
     * Version of system like 4.5.2
     * @var string
     */
    protected $sysVersion;

    /**
     * Major version of system like 4
     * @var int
     */
    protected $sysMajorVersion;

    /**
     * Major version of system like 5
     * @var int
     */
    protected $sysMinorVersion;

    /**
     * Major version of system like 2
     * @var int
     */
    protected $sysPathVersion;

    /**
     * Initialize class
     *
     * @param Illuminate\Foundation\Application $app
     * @param Illuminate\Config\Repository $config
     * @param Illuminate\Filesystem\Filesystem $files
     * @return void
     */
    public function __construct(Application $app, Repository $config, Filesystem $files)
    {
        $this->app = $app;
        $this->config = $config;
        $this->files = $files;

        $this->errors = new MessageBag();

        $this->path = app_path().'/'.$this->getConfig('path');
        $this->systemName = $this->getConfig('systemName');
        $this->sysVersion = $this->getConfig('sysVersion');
        $this->sysMajorVersion = $this->getConfig('sysMajorVersion');
        $this->sysMinorVersion = $this->getConfig('sysMinorVersion');
        $this->sysPathVersion = $this->getConfig('sysPathVersion');
        $this->configFile = $this->getConfig('configFile');
    }

    /**
     * Get all module from database and initialize
     *
     * @return void
     */
    public function start()
    {
        $modules = ModuleModel::where('module_status', 1)->get();
        foreach($modules as $module)
        {
            $path = $this->getModuleDirectory($module->module_name);
            if ($path)
            {
                $name = $module->module_name;
                $this->modules[$name] = new Module($this->app, $module->module_name, $path);
            }
        }
    }

    /**
     * Register modules with Moduel class
     *
     * @return void
     */
    public function register()
    {
        foreach ($this->modules as $module)
        {
            $module->register();
        }
        $modules = ModuleModel::where('module_status', 1)->get();

        foreach ($modules as $module)
        {
            if ($module->module_is_installed)
            {
                $this->app['events']->fire('modules.install.'.$module->module_name, null);
                $module->module_is_installed = 0;
            }
            if ($module->module_is_updated)
            {
                $this->app['events']->fire('modules.update.'.$module->module_name, null);
                $module->module_is_updated = 0;
            }
            if ($module->module_is_enabled)
            {
                $this->app['events']->fire('modules.enable.'.$module->module_name, null);
                $module->module_is_enabled = 0;
            }
            $module->save();
        }
    }

    /**
     * Enable module to load
     *
     * @param string #moduleName
     * @return bool
     */
    public function enable($moduleName)
    {
        $module = $this->findOrFalse('module_name', $moduleName);
        if ($module)
        {
            if(!$module->module_status)
            {
                $module->module_status = 1;
                $module->module_is_enabled = 1;
                $module->save();
                return true;
            }
            return false;
        }
        return false;
    }


    /**
     * Disable module
     *
     * @param string $moduleName
     * @return bool
     */
    public function disable($moduleName)
    {
        $module = $this->findOrFalse('module_name', $moduleName);
        if ($module)
        {
            if ($module->module_status)
            {
                $module->module_status = 0;
                $module->save();
                $this->app['events']->fire('modules.disable.'.$moduleName, null);
                return true;
            }
            return false;
        }
        return false;
    }

    /**
     * Get all list modules
     *
     * @return object
     */
    public function getListAllModules()
    {
        $modulesModel = ModuleModel::all();
        return $this->getListModules($modulesModel);
    }

    /**
     * Get list of enabled modules
     *
     * @return object
     */
    public function getListEnabledModules()
    {
        $modulesModel = ModuleModel::where('module_status', 1)->get();
        return $this->getListModules($modulesModel);
    }

    /**
     * Get list of disabled modules
     *
     * @return object
     */
    public function getListDisabledModules()
    {
        $modulesModel = ModuleModel::where('module_status', 0)->get();
        return $this->getListModules($modulesModel);
    }

    /**
     * Get list modules
     *
     * @param model $modulesModel
     * @return array
     */
    protected function getListModules($modulesModel)
    {
        $modules = array();
        foreach ($modulesModel as $module)
        {
            $modules[$module->module_name]['name'] = $this->def($module->module_name, 'name');
            $modules[$module->module_name]['icon'] = $this->getConfig('assets').'/'.$module->module_name.'/'.$this->def($module->module_name, 'icon');
            $modules[$module->module_name]['description'] = $this->def($module->module_name, 'description');
            $modules[$module->module_name]['author'] = $this->def($module->module_name, 'author');
            $modules[$module->module_name]['website'] = $this->def($module->module_name, 'website');
            $modules[$module->module_name]['version'] = $this->def($module->module_name, 'version');
        }
        return $modules;
    }

    /**
     * Move contents assets module to public directory
     *
     * @param string $moduleName
     * @return bool
     */
    public function buildAssets($moduleName)
    {
        $module = $this->getModuleDirectory($moduleName);
        if ($module)
        {
            $module .= '/assets';
            if ($this->files->exists($module))
            {
                if (!@$this->files->copyDirectory($module, $this->getAssetDirectory($moduleName)))
                {
                    $this->errors->add('build_assets', 'Unable to build assets');
                    return false;
                }
            }
            return true;
        }
        return false;
    }

    /**
     * Remove assets of a module
     *
     * @param string $moduleName
     * @return bool
     */
    public function removeAssets($moduleName)
    {
        $assets = $this->getAssetDirectory($moduleName);

        $this->files->deleteDirectory($assets);
        if ($this->files->exists($assets))
        {
            $this->errors->add('delete_assets', 'Unable to delete assets in: '.$assets);
            return false;
        }
        return true;
    }

    /**
     * Remove module directory
     *
     * @param string $moduleName
     * @return bool
     */
    protected function removeModuleDirectory($moduleName)
    {
        $this->files->deleteDirectory($this->getModuleDirectory($moduleName));
        if($this->files->exists($this->getModuleDirectory($moduleName)))
        {
            $this->errors->add('delete_files', 'Unable to delete '.$this->getModuleDirectory($moduleName));
            return false;
        }
        return true;
    }

    /**
     * Check system dependency
     *
     * @param string $version
     * @return bool
     */
    protected function sysVersionDependency($version)
    {
        $sysVersion = new Version($this->sysMajorVersion.'.'.$this->sysMinorVersion.'.'.$this->sysPathVersion);
        $needVersion = new Version($version);

        if (!$sysVersion->isPartOf($needVersion))
        {
            $this->errors->add('module_dependency_sys', 'This module not made for current version of '.$this->systemName.', made for '.$version);
            return false;
        }
        return false;
    }

    /**
     * Check module dependencies
     *
     * @param array $dependencies
     * @return bool
     */
    protected function checkDependency($dependencies = null)
    {
        $errors = array();

        if (is_null($dependencies)) $dependencies = array();

        $i = 0;
        $clean = true;
        foreach ($dependencies as $module => $version)
        {
            if ($module == $this->systemName)
            {
                if (!$this->sysVersionDependency($version))
                    $clean = false;

                continue;
            }

            $depModule = ModuleModel::where('module_name', $module)->first();
            if (!$depModule)
            {
                $this->errors->add("module_dependency_$i", "Module $module not installed");
                $clean = false;
            }
            else
            {
                $depVersion = $depModule->module_version;
                $needVersion = $version;

                $depVersion = new Version($depVersion);
                $needVersion = new Version($needVersion);

                if (!$depVersion->isPartOf($needVersion))
                {
                    $this->errors->add("module_dependency_$i", 'Module '.$module.' v'.$needVersion->getVersion().' must install, but '.$depVersion->getVersion().' installed.');
                    $clean = false;
                }
            }
            $i++;
        }

        if(!$clean)
            return false;

        return true;
    }

    /**
     * Accesss to errors object
     *
     * @return Illuminate\Support\MessageBag
     */
    public function getErrors()
    {
        $errors = $this->errors;
        $this->errors = new MessageBag;
        return $errors;
    }

    /**
     * Uninstall module and remove assets
     *
     * @param string $moduleName
     * @return bool
     */
    public function uninstall($moduleName)
    {
        $module = $this->findOrFalse('module_name', $moduleName);
        if ($module)
        {
            $version = $this->def($moduleName, 'version');
            if ($this->checkModuleDepends($moduleName, new Version($version)))
                return false;

            $this->app['events']->fire('modules.uninstall.'.$moduleName, null);

            $module->delete();

            if (!$this->removeAssets($moduleName))
                $this->errors->add('delete_assets', "Unable to delete assets $moduleName");

            if (!$this->removeModuleDirectory($moduleName))
                $this->errors->add('delete_module', "Unable to delete $moduleName");

            return true;
        }
        return false;
    }

    /**
     * Install module and build assets
     *
     * @param string $moduleName
     * @param string path of module
     * @return bool
     */
    public function install($tempPath, $moduleName)
    {
        //Check module dependecies
        $moduleDependency = $this->def($tempPath, 'require', true);
        if (!$moduleDependency = $this->checkDependency($moduleDependency))
            return false;

        //Move extracted module to modules path
        if (!$this->files->copyDirectory($tempPath, $this->path.'/'.$moduleName))
        {
            $this->errors->add('move_files_permission_denied', 'Permission denied in: '.$this->path.'/'.$moduleName);
            return false;
        }

        $this->buildAssets($moduleName);
        if (!$this->registerModule($moduleName))
        {
            $this->errors->add('register_module', 'Error in register module');
            return false;
        }
        return true;
    }

    /**
     * Register module
     *
     * @param string $moduleName
     * @return bool
     */
    protected function registerModule($moduleName)
    {
        $module = new ModuleModel;
        $module->module_name = $this->def($moduleName, 'name');
        $module->module_version = $this->def($moduleName, 'version');
        $module->module_status = 0;
        $module->module_is_enabled = 0;
        $module->module_is_installed = 1;
        $module->save();

        return true;
    }

    /**
     * Update metadate of module updated
     *
     * @param string $moduleName
     * @return void
     */
    protected function updateRegisteredModule($moduleName)
    {
        $module = $this->findOrFalse('module_name', $moduleName);
        $module->module_version = $this->def($moduleName, 'version');
        $module->module_is_updated = 1;
        $module->save();
    }


    /**
     * Initialize zip module
     *
     * @param string $archive path of module zip
     * @return bool
     */
    public function zipInit($archive)
    {
        $tempPath = $this->getAssetDirectory().'/#tmp/'.uniqid();

        $result = $this->traceZip($archive, $tempPath);

        $this->files->deleteDirectory($tempPath);
        return $result;
    }

    /**
     * Step by Step to install or update a module zip
     *
     * @param string $archive path of zip
     * @param string $archivePath
     * @return bool
     */
    protected function traceZip($archive, $tempPath)
    {
        if (!$archive = $this->extractZip($archive, $tempPath, true))
            return false;

        //Check module has requires file
        if (!$this->checkRequires($tempPath))
            return false;
        $moduleName = $this->def($tempPath, 'name', true);

        if ($this->moduleExists($moduleName))
            return $this->update($tempPath, $moduleName);

        else
            return $this->install($tempPath, $moduleName);
    }

    /**
     * Check module has required file
     *
     * @param string $modulePath
     * @return bool
     */
    protected function checkRequires($modulePath)
    {
        $requires = $this->getConfig('requires', array());

        foreach($requires as $key => $value)
        {
            if (is_array($value))
            {
                if (!$this->files->exists($modulePath.'/'.$key)) return false;
                $jsonFile = json_decode($this->app['files']->get($modulePath.'/'.$key), true);
                foreach($value as $key)
                {
                    if (is_array($jsonFile))
                    {
                        if (!array_key_exists($key, $jsonFile) || empty($jsonFile[$key]))
                        {
                            $this->errors->add('module_requires', 'This module has not requires files');
                            return false;
                        }
                    }
                    else
                    {
                        $this->errors->add($this->configFile, $this->configFile.' is corrupted.');
                        return false;
                    }
                }
            }
            else
            {
                if (!$this->files->exists($modulePath.'/'.$value))
                {
                    $this->errors->add('module_requires', 'This module has not requires files');
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * Get configuration module
     *
     * @param string $item
     * @param null|mixed $default
     * @return mixed
     */
    protected function getConfig($item, $default = null)
    {
        return $this->config->get('module::'.$item, $default);
    }

    /**
     * Update module
     *
     * @param string $newModulePath path of zip
     * @param string $moduleName
     * @return bool
     */
    protected function update($newModulePath, $moduleName)
    {
        $curModulePath = $this->getModuleDirectory($moduleName);

        $curVersion = $this->def($moduleName, 'version');
        $newVersion = $this->def($newModulePath, 'version', true);

        $curVersion = new Version($curVersion);
        $newVersion = new Version($newVersion);

        if (!$newVersion->greaterThan($curVersion))
        {
            $this->errors->add('update_version', 'Current version is greater than or equal uploaded version.');
            return false;
        }

        if ($this->checkModuleDepends($moduleName, $curVersion, $newVersion))
            return false;

        //Move and replace new version
        if (!$this->removeModuleDirectory($moduleName))
            return false;

        if (!$this->files->copyDirectory($newModulePath, $curModulePath))
        {
            $this->errors->add('move_files', 'Can not move new version files to '.$newModulePath);
            return false;
        }

        $this->removeAssets($moduleName);
        $this->buildAssets($moduleName);

        $this->updateRegisteredModule($moduleName);

        return true;
    }

    /**
     * Check others modules depends to this module or not, when update or delete module
     *
     * @param string $moduleName
     * @param Chee\Version\Version $curVersion for update required
     * @param Chee\Version\Version $newVersion for delete not required
     * @return bool
     */
    public function checkModuleDepends($moduleName, Version $curVersion, Version $newVersion = null)
    {
        $modules = ModuleModel::all();
        $clean = true;
        $i = 0;
        foreach ($modules as $module)
        {
            $depends = $this->def($module->module_name, 'require');
            if (array_key_exists($moduleName, $depends))
            {
                if (!is_null($newVersion)) //Check for update a module
                {
                    $dependVersion = new Version($depends[$moduleName]);
                    if (!$newVersion->isPartOf($dependVersion))
                    {
                        $clean = false;
                        $this->errors->add("module_depend_$i", "Can not update $moduleName, ".$module->module_name.' Depend to version '.$dependVersion->getOriginalVersion().' of '.$moduleName);
                    }
                }
                else //Check for delete a module
                {
                    $clean = false;
                    $this->errors->add("module_depend_$i", "Can not uninstall $moduleName, ".$module->module_name.' Depend this module.');
                }
            }
            $i++;
        }

        if (!$clean)
            return true;
        return false;
    }

    /**
     * Extract zip file
     *
     * @param string $archive path of archive
     * @param string $target
     * @param bool $deleteSource
     * @return Chee\Pclzip\Pclzip|false
     */
    protected function extractZip($archive, $target, $deleteSource = false)
    {
        if (!$this->files->exists($target))
        {
            $this->files->makeDirectory($target, 0777, true);
        }

        $archive = new Pclzip($archive);

        if ($archive->extract(PCLZIP_OPT_PATH, $target) == 0)
        {
            $this->errors->add('extract_zip', $archive->error_string);
            return false;
        }

        if ($deleteSource)
            $this->files->delete($archive->zipname);

        return $archive;
    }

    /**
     * Check if module exists
     *
     * @param string $moduleName
     * @return bool
     */
    public function moduleExists($moduleName)
    {
        $module = $this->findOrFalse('module_name', $moduleName);
        if ($module)
            return true;

        return false;
    }

    /**
     * Find one record from model
     *
     * @param string $field
     * @param string $name
     * @return object|false
     */
    public function findOrFalse($field, $name) {
        $module = ModuleModel::where($field, $name)->first();
        return !is_null($module) ? $module : false;
    }

    /**
     * Get path of specific module
     *
     * @param string $moduleName name of module
     * @return string|false
     */
    public function getModuleDirectory($moduleName)
    {
        if ($this->files->exists($this->path.'/'.$moduleName))
            return $this->path.'/'.$moduleName;

        else
            return false;
    }

    /**
     * Get assets path of speciic module
     *
     * @param string|null $moduleName name of module
     * @return string
     */
    public function getAssetDirectory($moduleName = null)
    {
        if ($moduleName)
            return public_path().'/'.$this->getConfig('assets').'/'.$moduleName;

        return public_path().'/'.$this->getConfig('assets').'/';
    }

    /**
     * Get module.json data of module
     *
     * @param string $moduleName name of module or address of
     * @param string $key key of array
     * @param string $isAddress if first parameter is address this parameter should be true
     * @return array|string|null
     */
    protected function def($moduleName, $key = null, $isAddress = false)
    {
        if($isAddress)
            $definition = json_decode($this->app['files']->get($moduleName.$this->configFile), true);
        else
            $definition = json_decode($this->app['files']->get($this->getModuleDirectory($moduleName).$this->configFile), true);

        if ($key)
            if (isset($definition[$key]))
                return $definition[$key];
            else
                return null;
        else
            return $definition;
    }
}
