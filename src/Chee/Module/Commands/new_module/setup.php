<?php namespace Modules\#moduleName;

class Setup
{

    public function __construct()
    {
        $this->name = "#moduleName";
    }

    public function subscribe($events)
    {
        $events->listen('modules.install.' . $this->name, 'Modules\#moduleName\Setup@install');
        $events->listen('modules.uninstall.' . $this->name, 'Modules\#moduleName\Setup@uninstall');
        $events->listen('modules.enable.' . $this->name, 'Modules\#moduleName\Setup@enable');
        $events->listen('modules.disable.' . $this->name, 'Modules\#moduleName\Setup@disable');
        $events->listen('modules.update.' . $this->name, 'Modules\#moduleName\Setup@update');
    }

    public function install($event)
    {

    }

    public function uninstall($event)
    {

    }

    public function disable($event)
    {

    }

    public function enable($event)
    {

    }

    public function update($event)
    {

    }
}
