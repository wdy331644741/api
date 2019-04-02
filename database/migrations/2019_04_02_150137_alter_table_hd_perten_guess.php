<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableHdPertenGuess extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('hd_perten_guess', function (Blueprint $table) {
            $table->unsignedTinyInteger('alert')->default(0)->after('status');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('hd_perten_guess', function (Blueprint $table) {
            $table->dropColumn('alert');
        });
    }
}
