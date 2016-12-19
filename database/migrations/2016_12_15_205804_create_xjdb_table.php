<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateXjdbTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('xjdb', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id');
            $table->string('award_name')->default('');
            $table->string('uuid', 64)->default('');
            $table->tinyInteger('type');  //7:现金 3:体验金
            $table->tinyInteger('status')->default(0); 
            $table->string('ip', 15)->default('');
            $table->text('remark')->default('');
            $table->timestamps();

            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('xjdb');
    }
}
