<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableHdHockeyGuess extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('hd_hockey_guess', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id')->default(0)->comment('用户id');
            $table->integer('config_id')->default(0)->comment('关联配置id');
            $table->string('match_date',10)->default('')->comment('押注的对阵1-2 意思：（中国-荷兰）');
            $table->string('find_name',64)->default('')->comment('押注的第几场first、second、third');
            $table->integer('num')->default(0)->comment('押注数量');
            $table->decimal('amount',10,2)->default(0)->comment('获取现金金额');
            $table->tinyInteger('type')->default(0)->comment('类型1普通对阵，2冠军场');
            $table->tinyInteger('status')->default(0)->comment('是否中奖，0未中，1中');
            $table->timestamps();
            $table->index('user_id');
            $table->index('config_id');
            $table->index('math_date');
            $table->index('find_name');
            $table->index('type');
            $table->comment = '曲棍球压注表';
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('hd_hockey_guess');
    }
}
