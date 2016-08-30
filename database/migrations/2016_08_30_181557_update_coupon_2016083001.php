<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateCoupon2016083001 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('coupon', function (Blueprint $table) {
            $table->tinyInteger('import_status',false,true)->nullable()->default(0);//导出状态0未生成1正在导出2导出成功
            $table->tinyInteger('export_status',false,true)->nullable()->default(0);//导出状态0未生成1正在导出2导出成功
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('coupon', function (Blueprint $table) {
            $table->dropColumn('import_status');//导入状态1正在导入2导入成功
            $table->dropColumn('export_status');//导出状态1正在导出2导出成功
        });
    }
}
