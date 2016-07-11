<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateAward1Table extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('award_1', function (Blueprint $table) {
            $table->dateTime('rate_increases_start')->nullable()->default(NULL)->change();//加息时长开始时间
            $table->dateTime('rate_increases_end')->nullable()->default(NULL)->change();//加息时长结束时间
            $table->dateTime('effective_time_start')->nullable()->default(NULL)->change();//有效时间开始时间
            $table->dateTime('effective_time_end')->nullable()->default(NULL)->change();//有效时间结束时间
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
