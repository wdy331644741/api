<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateInPrizetypeTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('in_prizetypes', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('banner');
            $table->tinyInteger('is_online')->default(0);
            $table->Integer('sort')->default(0);
            $table->softDeletes();
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
        Schema::drop('in_prizetypes');
    }
}
