<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMoneyShareRelationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('money_share_relations', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id');
            $table->integer('invite_user_id');
            $table->string('identify');
            $table->string('tag');
            $table->string('award_name')->default('');
            $table->tinyInteger('type');  //7:现金 3:体验金
            $table->tinyInteger('status')->default(0);
            $table->string('ip', 15)->default('');
            $table->text('remark')->default('');
            $table->timestamps();

            $table->index('user_id');
            $table->index('tag');
            $table->index('invite_user_id');
            $table->index('identify');
        });
        //
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('money_share_relations');
    }
}
