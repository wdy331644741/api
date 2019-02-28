<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class HdCustomAwardTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('hd_custom_award', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('custom_id');
            $table->string('name');//名称
            $table->float('award_money', 8, 4);//红包金额
            $table->unsignedTinyInteger('type')->default(1)->comment('奖品类型 1红包2加息');
            $table->unsignedTinyInteger('status')->default(0);
            $table->unsignedTinyInteger('investment_time');//出借期限
            $table->unsignedInteger('min')->default(0);
            $table->unsignedInteger('max')->default(0);
            $table->unsignedTinyInteger('effective_time_day');//有效时间天数
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
        Schema::drop('hd_custom_award');
    }
}
