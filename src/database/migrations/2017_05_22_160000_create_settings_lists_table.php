<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

/**
 * Class CreateSettingsListsTable
 */
class CreateSettingsListsTable extends Migration
{

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create(config('settings.db_table'), function (Blueprint $table)
		{
			$table->string('setting_key')->index()->unique();
			$table->binary('setting_value')->nullable();
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::dropIfExists(config('settings.db_table'));
	}

}
