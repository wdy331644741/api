<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTzyxjUniqueRecordTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tzyxj_unique_record', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id');
            $table->integer('number');
            $table->integer('amount');
            $table->integer('week');
            $table->timestamps();

            $table->index(['number', 'week']);
            $table->index(['amount', 'week']);
            $table->index('week');
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('tzyxj_unique_record');
    }
}
