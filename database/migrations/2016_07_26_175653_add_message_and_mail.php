<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddMessageAndMail extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //加息券
        Schema::table('award_1', function (Blueprint $table) {
            $table->string('message',255)->nullable()->default(NULL);//短信模板
            $table->string('mail',255)->nullable()->default(NULL);//站内信模板
        });
        //直抵红包&百分比红包
        Schema::table('award_2', function (Blueprint $table) {
            $table->string('message',255)->nullable()->default(NULL);//短信模板
            $table->string('mail',255)->nullable()->default(NULL);//站内信模板
        });
        //体验金
        Schema::table('award_3', function (Blueprint $table) {
            $table->string('message',255)->nullable()->default(NULL);//短信模板
            $table->string('mail',255)->nullable()->default(NULL);//站内信模板
        });
        //优惠券
        Schema::table('coupon', function (Blueprint $table) {
            $table->string('message',255)->nullable()->default(NULL);//短信模板
            $table->string('mail',255)->nullable()->default(NULL);//站内信模板
        });
        //兑换码
        Schema::table('redeem_award', function (Blueprint $table) {
            $table->string('message',255)->nullable()->default(NULL);//短信模板
            $table->string('mail',255)->nullable()->default(NULL);//站内信模板
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //加息券
        Schema::table('award_1', function (Blueprint $table) {
            $table->dropColumn('message');//短信模板
            $table->dropColumn('mail');//站内信模板
        });
        //直抵红包&百分比红包
        Schema::table('award_2', function (Blueprint $table) {
            $table->dropColumn('message');//短信模板
            $table->dropColumn('mail');//站内信模板
        });
        //体验金
        Schema::table('award_3', function (Blueprint $table) {
            $table->dropColumn('message');//短信模板
            $table->dropColumn('mail');//站内信模板
        });
        //优惠券
        Schema::table('coupon', function (Blueprint $table) {
            $table->dropColumn('message');//短信模板
            $table->dropColumn('mail');//站内信模板
        });
        //兑换码
        Schema::table('redeem_award', function (Blueprint $table) {
            $table->dropColumn('message');//短信模板
            $table->dropColumn('mail');//站内信模板
        });
    }
}
