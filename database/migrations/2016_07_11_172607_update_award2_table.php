<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateAward2Table extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('award_2', function (Blueprint $table) {
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
