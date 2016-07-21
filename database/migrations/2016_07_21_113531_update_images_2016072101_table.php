<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateImages2016072101Table extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('images', function (Blueprint $table) {
            $table->dropColumn('http_url');//图片绝对路径
            $table->dropColumn('img_path');//图片绝对路径
            $table->string('file_name',255);//图片文件名
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('images', function (Blueprint $table) {
            $table->string('http_url',255);//图片跳转地址
            $table->string('img_path',255);//图片绝对路径
            $table->dropColumn('file_name');//图片文件名
        });
    }
}
