<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateTableHdWorldCupSupport extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('hd_world_cup_support', function (Blueprint $table) {
            $table->tinyInteger('status')->default(0)->comment('状态0失败1成功; 仅计算发奖时使用');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('hd_world_cup_support', function (Blueprint $table) {
            $table->dropColumn(["status"]);
        });
    }
}
