<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateAward22016072701 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('award_2', function (Blueprint $table) {
            $table->float('percentage',8,4)->change();//红包百分比
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('award_2', function (Blueprint $table) {
            $table->integer('percentage',false,true)->change();//红包百分比
        });
    }
}
