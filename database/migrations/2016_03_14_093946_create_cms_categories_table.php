<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCmsCategoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cms_categories', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('alias_name');
            $table->integer('cur_version');
            $table->integer('latest_version');
            $table->boolean('enable');
            $table->string('url');
            $table->integer('platform');
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
        Schema::drop('cms_categories');
        //
    }
}
