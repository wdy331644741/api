<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTreasureTable2017031501 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('treasure', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id');
            $table->decimal('amount',10,2)->default(0);
            $table->string('award_name')->default('');
            $table->string('uuid', 64)->default('');
            $table->tinyInteger('type')->comment("1:铜宝箱 2:银宝箱 3:金宝箱");
            $table->tinyInteger('status')->default(0);
            $table->string('ip', 15)->default('');
            $table->string('user_agent', 256)->default('');
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
        Schema::drop('treasure');
    }
}
