<?php namespace Chee\Module\Facades;

use Illuminate\Support\Facades\Facade;

class CheeModule extends Facade {

    /**
     * Get the registered name of the component
     *
     * @return string
     */
    protected static function getFacadeAccessor() {
        return 'chee-module';
    }
}
