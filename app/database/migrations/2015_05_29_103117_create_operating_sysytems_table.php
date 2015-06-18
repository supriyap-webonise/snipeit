<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateOperatingSysytemsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('operating_systems', function ($table) {
        $table->increments('id');
        $table->string('name');
        $table->timestamps();
        $table->softDeletes();
         });
        $date = new \DateTime;
        DB::table('operating_systems')->insert(array(
            array('name' => 'windows 7 (32 bit)','created_at' => $date,
			'updated_at' => $date),
            array('name' => 'windows 7 (64 bit)','created_at' => $date,
                'updated_at' => $date),
            array('name' => 'windows 8 (32 bit)','created_at' => $date,
                'updated_at' => $date),
            array('name' => 'windows 8 ( 64 bit)','created_at' => $date,
                'updated_at' => $date),
            array('name' => 'windows 8.1 (32 bit)','created_at' => $date,
                'updated_at' => $date),
            array('name' => 'windows 8.1 (64 bit)','created_at' => $date,
                'updated_at' => $date),
            array('name' => 'Ubuntu 12.04 (32 bit)','created_at' => $date,
                'updated_at' => $date),
            array('name' => 'Ubuntu 12.04 (64 bit)','created_at' => $date,
                'updated_at' => $date),
            array('name' => 'Ubuntu 14.04 (32 bit)','created_at' => $date,
                'updated_at' => $date),
            array('name' => 'Ubuntu 14.04 (64 bit)','created_at' => $date,
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
        Schema::drop('operating_systems');
	}

}
