<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCmsValuesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cms_values', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('type_id');
            $table->string('version');
            $table->integer('value_1');
            $table->string('value_2');
            $table->text('value_3');
            $table->timestamps();
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
        Schema::drop('cms_values');
        //
    }
}
