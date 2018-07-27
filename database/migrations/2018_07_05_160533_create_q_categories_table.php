<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateQCategoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('q_categories', function (Blueprint $table) {
            $table->increments('id');
            $table->string('title');
            $table->string('icon')->default('');
            $table->unsignedTinyInteger('status')->default(0);
            $table->integer('sort')->default(0);
            $table->text('remark');
            $table->timestamps();
            $table->comment = '问题分类';
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('q_categories');
    }
}
