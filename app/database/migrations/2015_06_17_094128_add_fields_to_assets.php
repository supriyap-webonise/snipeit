<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddFieldsToAssets extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
        Schema::table('assets', function ($table) {
            $table->integer('ram_id')->nullable()->default(NULL);
            $table->integer('device_id')->nullable()->default(NULL);
            $table->integer('os_id')->nullable()->default(NULL);
        });
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
        Schema::table('assets', function ($table) {
            $table->dropColumn('ram');
            $table->dropColumn('device_id');
            $table->dropColumn('os_id');
        });
	}

}
