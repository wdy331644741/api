<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateActivityJoins2016080201 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('activity_joins', function (Blueprint $table) {
            $table->dropColumn('award_type');//删除奖品类型
            $table->dropColumn('award_id');//删除奖品id
            $table->dropColumn('award_name');//删除奖品名称
            $table->tinyInteger('status',false,true)->nullable()->default(null);//发奖状态 1频次验证不通过2规则不通过3发奖成功
            $table->tinyInteger('trigger_type',false,true)->nullable()->default(null);//触发类型
            $table->text('remark')->nullable();//备注
            $table->text('invite_remark')->nullable();//邀请人备注
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('activity_joins', function (Blueprint $table) {
            $table->dropColumn('status');//删除发奖状态
            $table->dropColumn('trigger_type');//触发类型
            $table->dropColumn('remark');//备注
            $table->dropColumn('invite_remark');//邀请人备注
            $table->integer('award_type')->nullable()->default(null);//奖品类型
            $table->integer('award_id')->nullable()->default(null);//奖品id
            $table->integer('award_name')->nullable()->default(null);//奖品名称
        });
    }
}
