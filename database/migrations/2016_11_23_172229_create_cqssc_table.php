<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCqsscTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cqssc', function (Blueprint $table) {
            $table->increments('id');
            $table->string('expect');
            $table->integer('opencode');
            $table->datetime('opentime');
            $table->integer('opentimestamp');
            $table->unique('expect');
            $table->timestamps();
            $table->index('expect');
            $table->index('opentimestamp');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('cqssc');
    }
}
