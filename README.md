# Chee Module [in development]
> a module manager for laravel 4

## Install

update `composer.json` in laravel root with:

```json
"require": {
	"laravel/framework": "4.2.*",
	"chee/module": "dev-master"
},
```
and run `composer update`. then run:

```terminal
sudo php artisan migrate --package=chee/module
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
