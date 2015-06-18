<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddDevices extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
        Schema::create('devices', function ($table) {
            $table->increments('id');
            $table->string('name');
            $table->timestamps();
            $table->softDeletes();
        });
        $date = new \DateTime;
        DB::table('devices')->insert(array(
            array('name' => 'laptop','created_at' => $date,
                'updated_at' => $date),
            array('name' => 'desktop','created_at' => $date,
                'updated_at' => $date),
            array('name' => 'printer','created_at' => $date,
                'updated_at' => $date),
            array('name' => 'network device','created_at' => $date,
                'updated_at' => $date),
            array('name' => 'imac','created_at' => $date,
                'updated_at' => $date),
            array('name' => 'mac mini','created_at' => $date,
                'updated_at' => $date)
        ));
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
        Schema::drop('devices');
	}

}
