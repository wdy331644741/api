<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableHdWeeksGuess extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('hd_weeks_guess', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id')->comment('用户id');
            $table->unsignedSmallInteger('period')->comment('期数id');
            $table->unsignedTinyInteger('type')->comment('类型');
            $table->unsignedSmallInteger('number')->default(0)->comment('次数');
            $table->text('remark')->default('')->comment('备注');
            $table->timestamps();
            $table->index('user_id');
            $table->index('type');
            $table->comment = '周末专享竞猜';
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('hd_weeks_guess');
    }
}
