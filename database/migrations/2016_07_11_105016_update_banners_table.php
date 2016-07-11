<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateBannersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('banners', function (Blueprint $table) {
            $table->timestamp('release_time')->nullable();//发布时间
            $table->timestamp('activity_time')->nullable();//图片的活动时间
            $table->string('name',64)->nullable()->change();//名称
            $table->text('desc',64)->nullable()->change();//描述
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('banners', function (Blueprint $table) {
            $table->dropColumn('release_time');//发布时间
            $table->dropColumn('activity_time');//图片的活动时间
        });
    }
}
