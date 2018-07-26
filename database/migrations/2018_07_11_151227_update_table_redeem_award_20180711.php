<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateTableRedeemAward20180711 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('redeem_award', function (Blueprint $table) {
            $table->tinyInteger('type',false,true)->after("id")->default(0)->comment("兑换码类型，0普通1口令红包");
            $table->integer('use_num',false,true)->after("number")->default(0)->comment("领取数量");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('redeem_award', function (Blueprint $table) {
            $table->dropColumn(["type","use_num"]);
        });
    }
}
