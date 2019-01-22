<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateHdWeeksGuess extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('hd_weeks_guess', function (Blueprint $table) {
            $table->unsignedTinyInteger('status')->default(0)->after('number');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('hd_weeks_guess', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
}
