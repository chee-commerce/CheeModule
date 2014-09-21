<?php namespace Chee\Module;

use Illuminate\Foundation\Application;
use Illuminate\Config\Repository;
use Illuminate\Filesystem\Filesystem;
use Pclzip;

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
     * Keep errors
     * @var array
     */
    protected $errors = array();

    /**
     * use CheeModule for?
     * @var string
     */
    protected $systemName = 'CheeCommerce';

    /**
     * Version of system like 4.5.2
     * @var string
     */
    protected $sysVersion = CH_VERSION;

    /**
     * Major version of system like 4
     * @var int
     */
    protected $sysMajorVersion = CH_MAJOR_VERSION;

    /**
     * Major version of system like 5
     * @var int
     */
    protected $sysMinorVersion = CH_MINOR_VERSION;

    /**
     * Major version of system like 2
     * @var int
     */
    protected $sysPathVersion = CH_PATH_VERSION;

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
        $modules = ModuleModel::where('is_enabled', 1)->orWhere('is_installed', 1)->orWhere('is_updated', 1)->get();
        foreach ($modules as $module)
        {
            if ($module->is_installed)
            {
                $this->app['events']->fire('modules.install.'.$module->name, null);
                $module->is_installed = 0;
            }
            if ($module->is_updated)
            {
                $this->app['events']->fire('modules.update.'.$module->name, null);
                $module->is_updated = 0;
            }
            if ($module->is_enabled)
            {
                $this->app['events']->fire('modules.enable.'.$module->name, null);
                $module->is_enabled = 0;
            }
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
                $module->is_enabled = 1;
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

    /**
     * Install module and build assets
     * @param $name string
     * @return bool
     */
    public function install($name)
    {
        $module = $this->findOrFalse('name', $name);
        if ($module)
        {
            if (!$module->installed)
            {
                //Check require version for system and module dependecies
                $cheeCommerceRequire = $this->def($name, $this->systemName);
                $moduleDependency = $this->def($name, 'require');

                $cheeCommerceRequire = $this->CheeCommerceCompliancy($cheeCommerceRequire);
                $moduleDependency = $this->checkStrictDependency($moduleDependency);

                if (!$cheeCommerceRequire || !$moduleDependency)
                {
                    return false;
                }

                if (!$this->installProccess($module))
                {
                    return false;
                }
                return true;
            }
        }
        return false;
    }

    /**
     * Force install a module without check version of dependencies and version of system
     * @param $name string
     * @return bool
     */
    public function forceInstall($name)
    {
        $module = $this->findOrFalse('name', $name);
        if ($module)
        {
            if (!$module->installed)
            {
                //Check module dependecies is installed
                $moduleDependency = $this->def($name, 'require');
                $moduleDependency = $this->checkDependency($moduleDependency);

                if (!$moduleDependency)
                {
                    return false;
                }

                if (!$this->installProccess($module))
                {
                    return false;
                }
                return true;

            }
        }
        return false;
    }

    protected function installProccess($module)
    {
        $module->installed = 1;
        $module->status = 1;
        $module->is_enabled = 1;
        $module->is_installed = 1;
        $module->save();
        $this->buildAssets($module->name);
        return true;
    }

    /**
     * Check Chee Commerce Compliancy
     * @param $version string CheeCommerce version entered by module developer in module.json
     * @param bool
     */
    public function CheeCommerceCompliancy($version)
    {
        $error = array();

        preg_match("/(\d{1,}|[*])\.(\d{1,}|[*])\.(\d{1,}|[*])/", @$version['min'], $min);
        preg_match("/(\d{1,}|[*])\.(\d{1,}|[*])\.(\d{1,}|[*])/", @$version['max'], $max);

        if (!count($min) || !count($max))
        {
            $error['module.json'] = "module.json of this module is corrupted.";
            $this->$errors = $error;
            return false;
        }

        $min = $min[0];
        $max = $max[0];


        $minMajorOffset = strpos($min, '.');
        $minMajor = substr($min, 0, $minMajorOffset);

        $minMinorOffset = strpos($min, '.', $minMajorOffset + 1);
        $minMinor = substr($min, $minMajorOffset + 1, $minMinorOffset - $minMajorOffset - 1);

        $minPathOffset = $minMinorOffset + 1;
        $minPath = substr($min, $minMinorOffset + 1, strlen($min) - $minMinorOffset);

        $maxMajorOffset = strpos($max, '.');
        $maxMajor = substr($max, 0, $maxMajorOffset);

        $maxMinorOffset = strpos($max, '.', $maxMajorOffset + 1);
        $maxMinor = substr($max, $maxMajorOffset + 1, $maxMinorOffset - $maxMajorOffset - 1);

        $maxPathOffset = $maxMinorOffset + 1;
        $maxPath = substr($max, $maxMinorOffset + 1, strlen($max) - $maxMinorOffset);

        //Check min version
        if ($minMajor !== ANY)
        {
            if ($this->sysMajorVersion < (int) $minMajor)
            {
                $error['min'] = 'Minimum Chee Commerce release for this module is v'.$version['min'].' but Chee Commerce v'.$this->sysVersion.' is installed';
            }
            elseif ($this->sysMajorVersion === (int) $minMajor)
            {
                if ($this->sysMinorVersion < (int) $minMinor)
                {
                    $error['min'] = 'Minimum Chee Commerce release for this module is v'.$version['min'].' but Chee Commerce v'.$this->sysVersion.' is installed';
                }
                elseif ($this->sysMinorVersion === (int) $minMinor)
                {
                    if ($this->sysPathVersion < (int) $minPath)
                    {
                        $error['min'] = 'Minimum Chee Commerce release for this module is v'.$version['min'].' but Chee Commerce v'.$this->sysVersion.' is installed';
                    }
                }
            }
        }

        //Check max version
        if ($maxMajor !== ANY)
        {
            if ($this->sysMajorVersion > (int) $maxMajor)
            {
                $error['max'] = 'Maximum Chee Commerce release for this module is v'.$version['max'].' but Chee Commerce v'.$this->sysVersion.' is installed';
            }
            elseif ($this->sysMajorVersion === (int) $maxMajor)
            {
                if ($maxMinor !== ANY && $this->sysMinorVersion > (int) $maxMinor)
                {
                    $error['max'] = 'Maximum Chee Commerce release for this module is v'.$version['max'].' but Chee Commerce v'.$this->sysVersion.' is installed';
                }
                elseif ($this->sysMinorVersion === (int) $maxMinor)
                {
                    if ($maxPath !== ANY && $this->sysPathVersion > (int) $maxPath)
                    {
                        $error['max'] = 'Maximum Chee Commerce release for this module is v'.$version['max'].' but Chee Commerce v'.$this->sysVersion.' is installed';
                    }
                }
            }
        }

        if (count($error))
        {
            $this->errors['dependecies'][$this->systemName] = $error;
            return false;
        }
        return true;
    }

    /**
     * Check module dependency and this versions
     * @param dependencies array
     * @return bool
     */
    public function checkStrictDependency($dependencies)
    {
        $errors = array();

        if(is_null($dependencies)) $dependencies = array();

        foreach ($dependencies as $module => $version)
        {
            $deModule = ModuleModel::where('name', $module)->first();
            if ($deModule)
            {
                preg_match("/(\d{1,}|[*])\.(\d{1,}|[*])\.(\d{1,}|[*])/", @$version, $version);

                if (!count($version))
                {
                    $error['module.json'] = "module.json of this module is corrupted.";
                    $this->errors = $error;
                    return false;
                }

                $version = $version[0];

                $majorOffset = strpos($deModule->version, '.');
                $major = substr($deModule->version, 0, $majorOffset);

                $minorOffset = strpos($deModule->version, '.', $majorOffset + 1);
                $minor = substr($deModule->version, $majorOffset + 1, $minorOffset - $majorOffset - 1);

                $path = substr($deModule->version, $minorOffset + 1, strlen($deModule->version) - $minorOffset);

                $deMajorOffset = strpos($version, '.');
                $deMajor = substr($version, 0, $deMajorOffset);

                $deMinorOffset = strpos($version, '.', $deMajorOffset + 1);
                $deMinor = substr($version, $deMajorOffset + 1, $deMinorOffset - $deMajorOffset - 1);

                $dePath = substr($version, $deMinorOffset + 1, strlen($version) - $deMinorOffset);

                //Check dependency version
                if ($deMajor !== ANY)
                {
                    if ((int) $major !== (int) $deMajor)
                    {
                        $errors['up/downgrade'][$module.'#'.$version] = $module.' v'.$version.' but v'.$deModule->version.' installed';
                    }
                    elseif ((int) $major === (int) $deMajor)
                    {
                        if ($deMinor !== ANY && (int) $minor !== (int) $deMinor)
                        {
                            $errors['up/downgrade'][$module.'#'.$version] = $module.' v'.$version.' but v'.$deModule->version.' installed';
                        }
                        elseif ((int) $minor === (int) $deMinor)
                        {
                            if ($dePath !== ANY && (int) $path !== (int) $dePath)
                            {
                                $errors['up/downgrade'][$module.'#'.$version] = $module.' v'.$version.' but v'.$deModule->version.' installed';
                            }
                        }
                    }
                }
            }
            else
            {
                $errors['notinstalled'][$module.'#'.$version] = $module.' v'.$version.' but not installed';
            }
        }
        if(count($errors))
        {
            $this->errors['dependecies']['modules'] = $errors;
            return false;
        }
        return true;
    }

    /**
     * Check module dependency
     * @param dependencies array
     * @return bool
     */
    protected function checkDependency($dependencies)
    {
        $errors = array();

        if (is_null($dependencies)) $dependencies = array();

        foreach ($dependencies as $module => $version)
        {
            $deModule = ModuleModel::where('name', $module)->first();
            if (!$deModule)
            {
                $errors['notinstalled'][$module.'#'.$version] = $module.' v'.$version.' but not installed';
            }
        }

        if(count($errors))
        {
            $this->errors['dependecies']['modules'] = $errors;
            return false;
        }
        return true;
    }

    /**
     * Send errors
     * @return array
     */
    public function getErrors()
    {
        return $this->errors;
        $this->errors = array();
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
                if ($this->files->exists($this->getAssetDirectory($name)))
                {
                    $this->errors['uninstall']['forbidden']['assest'] = $this->getAssetDirectory($name);
                }
                return true;
            }
        }
        return false;
    }

    /**
     * Initialize zip module
     * @param $archive string path of zip
     * @param $moduleName string
     * @return bool
     */
    public function zipInit($archive, $moduleName = null)
    {
        $moduleName = $moduleName ?: pathinfo($archive)['filename'];

        if ($this->moduleExists($moduleName))
        { //Upgrade
            return $this->update($archive, $moduleName);
        }
        else
        { //Install
            return $this->moduleInit($archive, $moduleName);
        }
    }

    /**
     * Update module
    * @param $archive string path of zip
    * @param $moduleName string
    * @return bool
    */
    protected function update($archive, $moduleName)
    {
        $archive = $this->extractZip($archive, $this->getAssetDirectory().'#update');
        if (!$archive) return false;

        $this->files->deleteDirectory($archive->zipname);
        if ($this->files->exists($archive->zipname))
        {
            $this->errors['update']['forbidden']['delete'] = 'Can not delete directory.'.$archive->zipname;
        }

        //Check new version
        $updateModuleDir =  $this->getAssetDirectory().'#update/'.$moduleName;
        $ModuleDir = $this->getModuleDirectory($moduleName);

        $currentVersion = $this->def($moduleName, 'version');
        $updateVersion = $this->def($updateModuleDir.'/module.json', 'version', true);
        if (!$this->isNewerVersion($currentVersion, $updateVersion))
        {
            $this->errors['update']['version'] = 'This module has already been installed.';
            return false;
        }

        //Move and replace new version
        if (!$this->files->copyDirectory($updateModuleDir, $ModuleDir))
        {
            $this->errors['update']['move'] = 'Can not move files.';
            return false;
        }

        //Delete uploaded module directory
        if (!$this->files->deleteDirectory($updateModuleDir))
        {
            $this->errors['update']['forbidden']['delete'] = 'Can not delete directory.'.$updateModuleDir;
        }

        //Remove files specified
        if ($this->files->exists($ModuleDir.'/update.json'))
        {
            $removes = $this->def($ModuleDir.'/update.json', 'remove', true);
            if (array_key_exists('files', $removes))
            {
                foreach ($removes['files'] as $remove)
                {
                    $this->files->delete($ModuleDir.'/'.$remove);
                }
            }

            if (array_key_exists('directories', $removes))
            {
                foreach ($removes['directories'] as $remove)
                {
                    $this->files->deleteDirectory($ModuleDir.'/'.$remove);
                }
            }
            $this->files->delete($ModuleDir.'/update.json');
        }

        //Update database for update hook
        $module = $this->findOrFalse('name', $moduleName);
        $module->version = $this->def($moduleName, 'version');
        $module->is_updated = 1;
        $module->save();

        return true;
    }

    /**
     * detect new version
     * @param $currentVersion string
     * @param $updateVersion string
     * @return bool
     */
    protected function isNewerVersion($currentVersion, $updateVersion)
    {
        $errors = array();

        preg_match("/(\d{1,}|[*])\.(\d{1,}|[*])\.(\d{1,}|[*])/", @$currentVersion, $currentVersion);
        preg_match("/(\d{1,}|[*])\.(\d{1,}|[*])\.(\d{1,}|[*])/", @$updateVersion, $updateVersion);

        if (!count($currentVersion) || !count($updateVersion))
        {
            $error['module.json'] = "module.json of this module is corrupted.";
            $this->errors = $error;
            return false;
        }

        $currentVersion = $currentVersion[0];
        $updateVersion = $updateVersion[0];

        $curMajorOffset = strpos($currentVersion, '.');
        $curMajor = (int) substr($currentVersion, 0, $curMajorOffset);

        $curMinorOffset = strpos($currentVersion, '.', $curMajorOffset + 1);
        $curMinor = (int) substr($currentVersion, $curMajorOffset + 1, $curMinorOffset - $curMajorOffset - 1);

        $curPath = (int) substr($currentVersion, $curMinorOffset + 1, strlen($currentVersion) - $curMinorOffset);

        $upMajorOffset = strpos($updateVersion, '.');
        $upMajor = (int) substr($updateVersion, 0, $upMajorOffset);

        $upMinorOffset = strpos($updateVersion, '.', $upMajorOffset + 1);
        $upMinor = (int) substr($updateVersion, $upMajorOffset + 1, $upMinorOffset - $upMajorOffset - 1);

        $upPath = (int) substr($updateVersion, $upMinorOffset + 1, strlen($updateVersion) - $upMinorOffset);

        //Check dependency version
        if ($upMajor > $curMajor)
        {
            return true;
        }
        elseif ($upMajor === $curMajor)
        {
            if ($upMinor > $curMinor)
            {
                return true;
            }
            elseif ($upMinor === $curMinor)
            {
                if ($upPath > $curPath)
                {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Initialize zip module for install
     * @param $archive string path of zip
     * @param $moduleName string
     * @return bool
     */
    protected function moduleInit($archive, $moduleName)
    {
        $archive = $this->extractZip($archive, base_path().'/'.$this->modulesPath);
        if (!$archive) return false;

        $module = new ModuleModel;
        $module->name = $this->def($moduleName, 'name');
        $module->version = $this->def($moduleName, 'version');
        $module->save();

        $this->files->deleteDirectory($archive->zipname);
        if ($this->files->exists($archive->zipname))
        {
            $this->errors['moduleInit']['forbidden']['module'] = $archive->zipname;
        }

        return true;
    }

    /**
     * Extract zip file
     * @param $archive path of archive
     * @param $target
     * @param obj|false
     */
    protected function extractZip($archive, $target)
    {
        if (!$this->files->exists($target))
        {
            $this->files->makeDirectory($target, 0777, true);
        }

        $archive = new Pclzip($archive);

        if ($archive->extract(PCLZIP_OPT_PATH, $target) == 0)
        {
            $this->erros['extract'] = $archive->error_string;
            return false;
        }
        return $archive;
    }

    /**
     * Check if module exists
     * @param $name string
     * @return bool
     */
    public function moduleExists($name)
    {
        $module = $this->findOrFalse('name', $name);
        if ($module)
        {
            return true;
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
            if ($this->files->exists($this->getAssetDirectory($name)))
            {
                $this->errors['delete']['forbidden']['assest'] = $this->getAssetDirectory($name);
            }

            $this->files->deleteDirectory($this->getModuleDirectory($name));
            if($this->files->exists($this->getModuleDirectory($name)))
            {
                $this->errors['delete']['forbidden']['module'] = $this->getModuleDirectory($name);
            }

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
    public function getAssetDirectory($name = null)
    {
        if ($name)
        {
            return public_path().$this->config->get('module::assets').'/'.$name;
        }
        return public_path().$this->config->get('module::assets').'/';
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
     * @param $moduleName
     * @param $key string key of array
     * @param $isAddress string if first key is array second key can given
     * @return array|string
     */
    protected function def($moduleName, $key = null, $isAddress = false)
    {
        if($isAddress)
            $definition = json_decode($this->app['files']->get($moduleName), true);
        else
            $definition = json_decode($this->app['files']->get($this->getModuleDirectory($moduleName) . '/module.json'), true);

        if ($key)
            if (isset($definition[$key]))
                return $definition[$key];
            else
                return null;
        else
            return $definition;
    }
}
