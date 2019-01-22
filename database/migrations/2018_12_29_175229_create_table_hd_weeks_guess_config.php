<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableHdWeeksGuessConfig extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('hd_weeks_guess_config', function (Blueprint $table) {
            $table->increments('id');
            $table->string('period')->comment('期号');
            $table->string('special')->default('')->comment('专场名称');
            $table->string('home_team')->default('')->comment('主队');
            $table->string('guest_team')->default('')->comment('客队');
            $table->decimal('money')->default('0.00')->comment('奖金');
            $table->tinyInteger('status')->default(0)->comment('状态0下线 1上线');
            $table->tinyInteger('draw_status')->default(0)->comment('0待开奖/1已开奖');
            $table->tinyInteger('result')->default(0)->comment('比赛结果1主胜/2主平/3主负');
            $table->string('recent')->default('')->comment('近期赛况');
            $table->string('home_img')->default('')->comment('主队logo');
            $table->string('guest_img')->default('')->comment('客队logo');
            $table->unsignedSmallInteger('home_score')->comment('主队分数');
            $table->unsignedSmallInteger('guest_score')->comment('客队分数');
            $table->timestamp('start_time')->nullable()->comment('开始时间');
            $table->timestamp('end_time')->nullable()->comment('开始时间');
            $table->string('race_time')->default('')->comment('赛事时间');
            $table->text('remark')->default('')->comment('备注');
            $table->timestamps();
            $table->comment = '周末竞猜活动配置';
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('hd_weeks_guess_config');
    }
}
