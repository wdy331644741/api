<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateOneYuan2016112901 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('one_yuan', function (Blueprint $table) {
            $table->integer('period',false,true)->default(0)->after('priority');//期数
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('one_yuan', function (Blueprint $table) {
            $table->dropColumn('period');//期数
        });
    }
}
