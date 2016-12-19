<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateGlobalAttributesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('global_attributes', function (Blueprint $table) {
            $table->increments('id');
            $table->string('key', 256);
            $table->integer('number')->nullable()->default(null);
            $table->string('string', 512)->nullable()->default(null);
            $table->text('text')->nullable()->default(null);
            $table->timestamps();
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
        Schema::drop('global_attributes');
    }
}
