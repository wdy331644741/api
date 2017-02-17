<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateOpen189LogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('open189_logs', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id')->default(NULL);
            $table->integer('project_id')->default(NULL);
            $table->string('investment_amount')->default(NULL);
            $table->tinyInteger('is_first')->default(0);
            $table->tinyInteger('period')->default(NULL);
            $table->dateTime('buy_time')->default(NULL);
            $table->integer('type')->default(NULL);
            $table->tinyInteger('scatter_type')->default(NULL);
            $table->dateTime('register_time')->default(NULL);
            $table->tinyInteger('status')->default(0);
            $table->string('remark')->nullable()->default(NULL);
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
        Schema::drop('open189_logs');
    }
}
