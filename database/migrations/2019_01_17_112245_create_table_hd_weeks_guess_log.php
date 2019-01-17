<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableHdWeeksGuessLog extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('hd_weeks_guess_log', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id')->comment('用户id');
            $table->unsignedSmallInteger('period')->comment('期数id');
            $table->decimal('money')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('hd_weeks_guess_log');
    }
}
