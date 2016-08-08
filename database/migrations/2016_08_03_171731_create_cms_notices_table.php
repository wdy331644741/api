<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCmsNoticesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cms_notices', function (Blueprint $table) {
            $table->increments('id');
            $table->string('title');
            $table->text('content');
            $table->tinyInteger('sort')->default(0);
            $table->tinyInteger('release')->default(0);
            $table->dateTime('release_at')->nullable()->default(NULL);
            $table->tinyInteger('platform')->comment('0:全平台，1：pc端，2：移动端(ios,android)');
            $table->softDeletes();
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
        Schema::drop('cms_notices');
    }
}
