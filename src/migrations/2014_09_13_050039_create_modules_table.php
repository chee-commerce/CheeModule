<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateModulesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('modules', function(Blueprint $table)
		{
			$table->increments('module_id');
			$table->string('module_name', 150);
			$table->string('module_version', 50);
			$table->boolean('module_status');
			$table->boolean('module_is_enabled');
			$table->boolean('module_is_installed');
			$table->boolean('module_is_updated');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('modules');
	}

}
