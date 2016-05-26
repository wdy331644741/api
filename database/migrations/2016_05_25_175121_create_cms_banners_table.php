<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCmsBannersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cms_banners', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('sort');
            $table->string('paths');
            $table->string('links')->nullable()->default(NULL);
            $table->tinyInteger('release')->default(0);
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
        Schema::drop('cms_banners');
    }
}
