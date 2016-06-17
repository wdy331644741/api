<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAppStartpagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('app_startpages', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->tinyInteger('platform');
            $table->tinyInteger('enable')->default(0);
            $table->string('img1')->nullable()->default(NULL);
            $table->string('img2')->nullable()->default(NULL);
            $table->string('img3')->nullable()->default(NULL);
            $table->string('img4')->nullable()->default(NULL);
            $table->timestamp('online_time');
            $table->timestamp('offline_time');
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
        Schema::drop('app_startpages');
    }
}
