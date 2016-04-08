<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateActivityJoinsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('activity_joins', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('activity_id');
            $table->integer('user_id');
            $table->string('user_from');
            $table->tinyInteger('is_rereceive');
            $table->timestamp('rereceive_time');
            $table->timestamps();
        });
        Schema::table('activities', function (Blueprint $table) {
            $table->integer('join_nums')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('activity_joins');
    }
}
