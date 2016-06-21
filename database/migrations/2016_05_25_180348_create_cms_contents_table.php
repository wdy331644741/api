<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCmsContentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cms_contents', function (Blueprint $table) {
            $table->increments('id');
            $table->tinyInteger('type_id');
            $table->string('cover')->nullable()->default(NULL);
            $table->string('title');
            $table->text('content');
            $table->string('source')->nullable()->default(NULL);
            $table->integer('sort')->default(0);
            $table->tinyInteger('release')->default(0);
            $table->dateTime('release_at')->default(NULL);
            $table->tinyInteger('platform')->default(0)->commemt('0:全平台，1：pc端，2：移动端(ios,android)');
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
        Schema::drop('cms_contents');
    }
}
