<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeStockToHdPertenStockTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('hd_perten_stock', function (Blueprint $table) {
            $table->string('stock',10)->comment('股指收盘价')->change();
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
            $table->float('stock')->comment('股指收盘价')->change();
        });
    }
}
