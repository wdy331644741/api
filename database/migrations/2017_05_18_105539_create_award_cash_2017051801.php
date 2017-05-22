<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAwardCash2017051801 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('award_cash', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name',64)->default('')->comment("奖品名");//奖品名
            $table->decimal('money',10,2)->default(0)->comment("现金金额");//现金金额
            $table->string('type',64)->default('')->comment("现金类型");//现金金额
            $table->string('mail',255)->default('')->comment("站内信");//站内信
            $table->string('message',255)->default('')->comment("短信");//短信
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
        Schema::drop('award_cash');
    }
}
