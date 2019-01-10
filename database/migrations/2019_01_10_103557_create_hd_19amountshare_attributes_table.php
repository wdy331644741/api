<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateHd19amountshareAttributesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('hd_19amountshare_attributes', function (Blueprint $table) {
            $table->increments('id');
            $table->string('user_id')->index();
            $table->string('key')->index();
            $table->integer('datenum')->index();
            $table->decimal('amount')->index();
            $table->integer('number')->default(0);
            $table->index(['user_id','key','datenum']);
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
        Schema::drop('hd_19amountshare_attributes');
    }
}
