<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableHdPertenStock extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('hd_perten_stock', function (Blueprint $table) {
            $table->date('curr_time')->nullable()->comment('当天日期收盘价')->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('hd_perten_stock', function (Blueprint $table) {
            $table->timestamp('curr_time')->nullable()->comment('当天日期收盘价')->change();
        });
    }
}
