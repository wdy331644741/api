<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateRedeemAward2016080901 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('redeem_award', function (Blueprint $table) {
            $table->tinyInteger('export_status',false,true)->nullable()->default(0);//导出状态1正在导出2导出成功
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
            $table->dropColumn('export_status');//导出状态1正在导出2导出成功
        });
    }
}
