<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class PutOrderNumToIntegralmallsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('integralmalls', function (Blueprint $table) {
            $table->string('orderNum',50)->comment('兑吧订单号')->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('integralmalls', function (Blueprint $table) {
            $table->string('orderNum',20)->comment('兑吧订单号')->change();
        });
    }
}
