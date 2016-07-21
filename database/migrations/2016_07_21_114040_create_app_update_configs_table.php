<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAppUpdateConfigsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('app_update_configs', function (Blueprint $table) {
            $table->increments('id');
            $table->date('update_time');
            $table->string('force');
            $table->text('description');
            $table->string('url')->nullable()->default(NULL);
            $table->string('version');
            $table->string('size');
            $table->string('toggle')->default('off');
            $table->tinyInteger('platform')->default(1)->commnet('1:安卓，2:iOS，2:iPad');
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
        Schema::drop('app_update_configs');
    }
}
