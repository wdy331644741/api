<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class Update2HdWeeksGuessConfigTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('hd_weeks_guess_config', function (Blueprint $table) {
            $table->smallInteger('home_score')->comment('主队分数')->change();
            $table->smallInteger('guest_score')->comment('客队分数')->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('hd_weeks_guess_config', function (Blueprint $table) {
            $table->unsignedSmallInteger('home_score')->comment('主队分数')->change();
            $table->unsignedSmallInteger('guest_score')->comment('客队分数')->change();
        });
    }
}
