<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableHdHockeyCardAward extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('hd_hockey_card_award', function (Blueprint $table) {
            $table->increments('id');
            $table->string('award_name')->comment('用户id');
            $table->string('info')->default('')->comment('参数');
            $table->string('img')->default('')->comment('配图');
            $table->tinyInteger('status')->default(0)->comment('状态0未上线，1已上线');
            $table->timestamps();
            $table->comment = '曲棍球集卡实物奖配置表';
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('hd_hockey_card_award');
    }
}
