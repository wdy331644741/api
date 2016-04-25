<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateWx2wanglibaoTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('open_weixin', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id');
            $table->integer('openid');
            $table->timestamps();
        });
        Schema::table('open_weixin', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('open_weixin');
    }
}
