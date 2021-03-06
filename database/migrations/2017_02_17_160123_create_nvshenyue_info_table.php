<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateNvshenyueInfoTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('nvshenyue_info', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id');
            $table->string('source');
            $table->integer('number');
            $table->string('word');
            $table->timestamps();

            $table->index('user_id');
            $table->index('source');
            $table->index('word');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('nvshenyue_info');
    }
}
