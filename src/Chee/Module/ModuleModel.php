<?php namespace Chee\Module;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;

class ModuleModel extends Model {

    public $table = 'modules';

    public $timestamps = false;
    
    protected $primaryKey = 'module_id';
}
