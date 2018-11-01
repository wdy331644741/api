<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableHdHockeyGuessConfig extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('hd_hockey_guess_config', function (Blueprint $table) {
            $table->increments('id');
            $table->string('match_date',10)->default('')->comment('比赛日期');
            $table->string('first',5)->default('')->comment('第一场对阵1-2 意思：（中国-荷兰）');
            $table->string('first_score',5)->default('')->comment('第一场比分');
            $table->tinyInteger('first_result')->default(0)->comment('第一场结果1主胜，2平，3客胜');
            $table->string('second',5)->default('')->comment('第二场对阵1-2 意思：（中国-荷兰）');
            $table->string('second_score',5)->default('')->comment('第二场比分');
            $table->tinyInteger('second_result')->default(0)->comment('第二场结果1主胜，2平，3客胜');
            $table->string('third',5)->default('')->comment('第三场对阵1-2 意思：（中国-荷兰）');
            $table->string('third_score',5)->default('')->comment('第三场比分');
            $table->tinyInteger('third_result')->default(0)->comment('第三场结果1主胜，2平，3客胜');
            $table->tinyInteger('msg_status')->default(0)->comment('站内信发送状态0未发送，1已发送');
            $table->tinyInteger('champion_status')->default(0)->comment('是否是冠军场0否1是');
            $table->string("draw_info")->default('')->comment('中奖信息1_first_1,1_second_2,1_third_3方便查询find_name');
            $table->tinyInteger('open_status')->default(0)->comment('0未开奖，1已开奖，2已发送开奖结果');
            $table->text('remark')->default('')->comment('备注');
            $table->timestamps();
            $table->comment = '曲棍球竞猜活动配置表';
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('hd_hockey_guess_config');
    }
}
