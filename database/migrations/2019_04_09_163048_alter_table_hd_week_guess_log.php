<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableHdWeekGuessLog extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('hd_weeks_guess_log', function (Blueprint $table) {
            $table->text('remark')->default('')->comment('备注');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('hd_weeks_guess_log', function (Blueprint $table) {
            $table->dropColumn('remark');
        });
    }
}
