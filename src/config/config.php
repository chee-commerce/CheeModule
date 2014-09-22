<?php

return array(
    'path' => '/app/Modules',

    'assets' => '/modules', //=>public/modules/MODULE_NAME

    'include' => array(
        'helpers.php',
        'bindings.php',
        'observers.php',
        'filters.php',
        'composers.php',
        'routes.php'
    ),

    'requires' => array(
        'module.json' => array(
            'name',
            'version'
        )
    ),

);
