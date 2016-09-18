<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUserAttributesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_attributes', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id');
            $table->string('key', 256);
            $table->integer('number')->default(0);
            $table->string('string', 512)->default('');
            $table->text('text')->default('');
            $table->timestamps();
            $table->index('user_id');
            $table->index('key');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('user_attributes');
    }
}
