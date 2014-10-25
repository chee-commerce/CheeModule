<?php

return array(
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
        'routes.php',
        'setup.php'
    ),

    //required files for install a module
    'requires' => array(
        'module.json' => array(
            'name',
            'version'
        )
    ),

);
