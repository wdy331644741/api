<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateQCategoriesQuestionTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('q_categories_question', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('q_id');
            $table->unsignedInteger('c_id');
            $table->timestamps();
            $table->index(['c_id', 'q_id']);
            $table->comment = '分类问题关联表';
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('q_categories_question');
    }
}
