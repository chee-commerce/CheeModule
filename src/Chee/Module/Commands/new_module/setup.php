<?php namespace Modules\#moduleName;

class Setup
{

    public function __construct()
    {
        $this->name = "#moduleName";
    }

    public function subscribe($events)
    {
        $events->listen('modules.install.' . $this->name, 'Modules\#moduleName\#moduleName@install');
        $events->listen('modules.uninstall.' . $this->name, 'Modules\#moduleName\#moduleName@uninstall');
        $events->listen('modules.delete.' . $this->name, 'Modules\#moduleName\#moduleName@delete');
        $events->listen('modules.enable.' . $this->name, 'Modules\#moduleName\#moduleName@enable');
        $events->listen('modules.disable.' . $this->name, 'Modules\#moduleName\#moduleName@disable');
        $events->listen('modules.reset.' . $this->name, 'Modules\#moduleName\#moduleName@reset');
        $events->listen('modules.update.' . $this->name, 'Modules\#moduleName\#moduleName@update');
    }

    public function install($event)
    {

    }

    public function uninstall($event)
    {

    }

    public function delete($event)
    {

    }

    public function disable($event)
    {

    }

    public function enable($event)
    {

    }

    public function reset($event)
    {

    }

    public function update($event)
    {

    }
}
