<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCmsItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        Schema::create('cms_items', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('alias_name');
            $table->integer('category_id');
            $table->string('type');
            $table->integer('prority');
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
        Schema::drop('cms_items');
        //
    }
}
