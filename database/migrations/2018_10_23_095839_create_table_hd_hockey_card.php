<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableHdHockeyCard extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('hd_hockey_card', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id')->comment('用户id');
            $table->text('before')->default('')->comment('和卡之前');
            $table->text('after')->default('')->comment('和卡之后');
            $table->tinyInteger('type')->default(0)->comment('类型1冠军卡，2兑换实物卡');
            $table->tinyInteger('status')->default(0)->comment('实物卡兑换状态0未兑换，1已兑换');
            $table->timestamp('object_card_time')->nullable()->comment('实物卡获得时间');
            $table->integer('award_id')->default(0)->comment('实物奖id');
            $table->string('award_name')->default('')->comment('实物奖名字');
            $table->timestamps();
            $table->index('user_id');
            $table->index('type');
            $table->index('status');
            $table->index('award_id');
            $table->comment = '曲棍球集卡活动集卡表';
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('hd_hockey_card');
    }
}
