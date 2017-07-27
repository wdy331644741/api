<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableLifePrivilegeConfig20170721 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('life_privilege_config', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name')->default(NULL)->comment("充值的用户id");
            $table->tinyInteger('type',false,true)->default(0)->comment("商品类型1话费2流量");
            $table->tinyInteger('operator_type',false,true)->default(0)->comment("运营商类型1移动，2联通，3电信");
            $table->decimal('price',10,2)->default(0)->comment("网利宝出售价格");
            $table->tinyInteger('status',false,true)->default(0)->comment("上线状态 0已下线 1已上线");
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
        Schema::drop('life_privilege_config');
    }
}
