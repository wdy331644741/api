<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBbsGlobalConfigsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('bbs_global_configs', function (Blueprint $table) {
            $table->increments('id');
            $table->string('alias_name')->default('global_config');
            $table->tinyInteger('vip_level')->default(0);
            $table->tinyInteger('send_max')->default(3);
            $table->tinyInteger('comment_max')->default(5);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('bbs_global_configs');
    }
}
