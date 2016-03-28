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
            $table->integer('rule_id');
            $table->timestamps();
        });
        //子规则表
        //注册
        Schema::create('rule_register', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamp('min_time');
            $table->timestamp('max_time');
            $table->timestamps();
        });

        Schema::create('rule_invite', function (Blueprint $table) {
            $table->increments('id');
            $table->tinyInteger('is_invite');
            $table->tinyInteger('invites');
            $table->timestamps();
        });

        Schema::create('rule_userlevel', function (Blueprint $table) {
            $table->increments('id');
            $table->tinyInteger('min_level');
            $table->tinyInteger('max_level');
            $table->timestamps();
        });

        Schema::create('rule_usercredit', function (Blueprint $table) {
            $table->increments('id');
            $table->tinyInteger('min_credit');
            $table->tinyInteger('max_credit');
            $table->timestamps();
        });

        Schema::create('rule_balance', function (Blueprint $table) {
            $table->increments('id');
            $table->tinyInteger('min_balance');
            $table->tinyInteger('max_balance');
            $table->timestamps();
        });

        Schema::create('rule_firstcast', function (Blueprint $table) {
            $table->increments('id');
            $table->tinyInteger('min_firstcast');
            $table->tinyInteger('max_firstcast');
            $table->timestamps();
        });

        Schema::create('rule_cast', function (Blueprint $table) {
            $table->increments('id');
            $table->tinyInteger('min_cast');
            $table->tinyInteger('max_cast');
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
        Schema::drop('rules');
        //
    }
}
