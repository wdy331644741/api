<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateQQuestionTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('q_questions', function (Blueprint $table) {
            $table->increments('id');
            $table->string('title')->default('');
            $table->text('content')->default('');
            $table->integer('sort')->default(0);
            $table->unsignedTinyInteger('status')->default(0);
            $table->string('relative')->default('')->comment('关联问题id，以逗号分隔');
            $table->timestamps();
            $table->comment = '常见问题';
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('q_questions');
    }
}
