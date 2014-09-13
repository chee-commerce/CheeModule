# Chee Module [in development]
> a module manager for laravel 4

## Install

### with composer git repository

update `composer.json` in laravel root with:

```json
"require": {
	"laravel/framework": "4.2.*",
	"chee/module": "dev-master"
},
"repositories": [
    {
        "url": "http://192.168.10.30/shafiei/cheemodule.git",
        "type": "git"
    }
],
```
and run `composer update`. then run:

```terminal
sudo php artisan migrate --path=vendor/chee/module/src/Chee/Module/Migrations/
```


then add service provider and facades in `app/config/app.php`.

Service Provider:

```json
'Chee\Module\ModuleServiceProvider',
```

Facades:

```json
'CheeModule'	  => 'Chee\Module\Facades\CheeModule',
```
