<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateAward42016101401 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('award_4', function (Blueprint $table) {
            $table->dropColumn('integral_type');
            $table->dropColumn('integral_value');
            $table->dropColumn('integral_multiple');
            $table->string('limit_desc',32)->after('name')->nullable();//限制说明
            $table->string('mail',255)->after('name');//站内信
            $table->string('message',255)->after('name');//短信
            $table->integer('integral',false,true)->after('name');//会员值
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('award_4', function (Blueprint $table) {
            $table->varchar('mail');
            $table->dropColumn('message');
            $table->dropColumn('integral');
            $table->dropColumn('limit_desc');
        });
    }
}
