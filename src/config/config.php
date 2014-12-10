<?php

return array(
    //use CheeModule for?
    'systemName' => 'SystemName',

    //Version of system like 4.5.2
    'sysVersion' => '4.5.2',

    //Major version of system like 4
    'sysMajorVersion' => 4,

    //Major version of system like 5
    'sysMinorVersion' => 5,

    //Major version of system like 2
    'sysPathVersion' => 2,

    //Name of configuration file in every module by json format
    'configFile' => '/module.json',

    //path of module in app directory
    'path' => 'modules',

    //assets in public directory
    'assets' => 'modules',

    //include common files
    'include' => array(
        'helpers.php',
        'bindings.php',
        'observers.php',
        'filters.php',
        'composers.php',
        'routes.php'
    ),

    //required files for install a module
    'requires' => array(
        'module.json' => array(
            'name',
            'version'
        )
    ),

);
