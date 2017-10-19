<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddExpDayToBbsTasksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        Schema::table('bbs_tasks', function (Blueprint $table) {

            $table->tinyInteger('exp_day')->comment("有效天数");

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
        Schema::table('bbs_tasks', function (Blueprint $table) {
            $table->dropColumn(["exp_day"]);

        });
    }
}
