<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddRam extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
        Schema::create('ram', function ($table) {
            $table->increments('id');
            $table->string('name');
            $table->timestamps();
            $table->softDeletes();
        });
        $date = new \DateTime;
        DB::table('ram')->insert(array(
            array('name' => '2GB (2*1)','created_at' => $date,
                'updated_at' => $date),
            array('name' => '4GB (2*2)','created_at' => $date,
                'updated_at' => $date),
            array('name' => '4GB (4*1)','created_at' => $date,
                'updated_at' => $date),
            array('name' => '8GB (4*2)','created_at' => $date,
                'updated_at' => $date),
            array('name' => '8GB (8*1)','created_at' => $date,
                'updated_at' => $date),
            array('name' => 'other','created_at' => $date,
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
        Schema::drop('ram');
	}

}
