<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableHdSpring extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('hd_spring', function (Blueprint $table) {
            $table->unsignedSmallInteger('number')->default(1)->comment('兑换数量');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('hd_spring', function (Blueprint $table) {
            $table->dropColumn(['number']);
        });
    }
}
