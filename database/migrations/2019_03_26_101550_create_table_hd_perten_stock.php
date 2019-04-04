<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableHdPertenStock extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('hd_perten_stock', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedTinyInteger('period')->comment('期数');
            $table->float('stock')->comment('股指收盘价');
            $table->float('change')->comment('振幅');
            $table->unsignedTinyInteger('change_status')->default(0)->comment('1涨/2跌');
            $table->unsignedInteger('draw_number')->comment('收盘价后四位倒序，中奖号码');
            $table->unsignedTinyInteger('open_status')->default(0)->comment('0未发奖/1已发奖');
            $table->timestamp('curr_time')->nullable()->comment('当天日期收盘价');
            $table->text('remark')->default('');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('hd_perten_stock');
    }
}
