<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCmsOpinionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cms_opinions', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id');
            $table->text('content');
            $table->text('reply')->nullable()->default(NULL);
            $table->tinyInteger('platform')->default(0)->comment('1:ios,2:安卓，3:pc,4:h5');
            $table->string('user_agent')->nullable()->default(NULL);
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
        Schema::drop('cms_opinions');
    }
}
