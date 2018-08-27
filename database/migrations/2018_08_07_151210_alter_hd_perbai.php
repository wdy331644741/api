<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterHdPerbai extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('hd_perbai', function (Blueprint $table) {
            $table->unsignedInteger('draw_number')->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('hd_perbai', function (Blueprint $table) {
            $table->string('draw_number')->change();
        });
    }
}
