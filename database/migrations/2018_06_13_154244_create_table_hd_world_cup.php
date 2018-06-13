<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableHdWorldCup extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('hd_world_cup', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id')->comment('用户id');
            $table->decimal('amount', 8, 2)->unsigned()->default('0.00')->comment('现金');
            $table->string('uuid', 64)->default('')->comment('跟刘奇那对接的唯一id');
            $table->tinyInteger('status')->default(0)->comment('状态0失败1成功');
            $table->text('remark')->default('')->comment('备注');
            $table->timestamps();
            $table->index('user_id');
            $table->comment = '世界杯瓜分现金发奖记录表';
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('hd_world_cup');
    }
}
