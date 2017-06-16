<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableDazhuanpan20170616 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('dazhuanpan', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id');
            $table->string('award_name')->default('');
            $table->string('alias_name')->default('');
            $table->string('uuid', 64)->default('');
            $table->string('type')->default('');
            $table->tinyInteger('status')->default(0);
            $table->string('ip', 15)->default('');
            $table->string('user_agent', 256)->default('');
            $table->text('remark')->default('');
            $table->timestamps();
            $table->index('user_id');
            $table->index('type');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('dazhuanpan');
    }
}
