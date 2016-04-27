<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRulesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('rules', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('activity_id');
            $table->tinyInteger('rule_type', false, true);
            $table->string('rule_info');
            $table->timestamps();
        });
        /*//子规则表
        Schema::create('rule_channel', function (Blueprint $table) {
            $table->increments('id');
            $table->string('channels')->default(0);//多个渠道用';'分隔
            $table->timestamps();
        });

        //注册
        Schema::create('rule_register', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamp('min_time')->nullable()->default(NULL);
            $table->timestamp('max_time')->nullable()->default(NULL);
            $table->timestamps();
        });

        Schema::create('rule_invite', function (Blueprint $table) {
            $table->increments('id');
            $table->tinyInteger('is_invite');
            $table->timestamps();
        });

        Schema::create('rule_invitenum', function (Blueprint $table) {
            $table->increments('id');
            $table->tinyInteger('invitenum');
            $table->timestamps();
        });

        Schema::create('rule_userlevel', function (Blueprint $table) {
            $table->increments('id');
            $table->tinyInteger('min_level')->nullable()->default(NULL);
            $table->tinyInteger('max_level')->nullable()->default(NULL);
            $table->timestamps();
        });

        Schema::create('rule_usercredit', function (Blueprint $table) {
            $table->increments('id');
            $table->tinyInteger('min_credit')->nullable()->default(NULL);
            $table->tinyInteger('max_credit')->nullable()->default(NULL);
            $table->timestamps();
        });

        Schema::create('rule_balance', function (Blueprint $table) {
            $table->increments('id');
            $table->tinyInteger('min_balance')->nullable()->default(NULL);
            $table->tinyInteger('max_balance')->nullable()->default(NULL);
            $table->timestamps();
        });

        Schema::create('rule_firstcast', function (Blueprint $table) {
            $table->increments('id');
            $table->tinyInteger('min_firstcast')->nullable()->default(NULL);
            $table->tinyInteger('max_firstcast')->nullable()->default(NULL);
            $table->timestamps();
        });

        Schema::create('rule_cast', function (Blueprint $table) {
            $table->increments('id');
            $table->tinyInteger('min_cast')->nullable()->default(NULL);
            $table->tinyInteger('max_cast')->nullable()->default(NULL);
            $table->timestamps();
        });*/

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('rules');
    }
}
