<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateActivitiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('activities', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('alias_name');
            $table->timestamp('start_at')->nullable()->defaule(NULL);
            $table->timestamp('end_at')->nullable()->defaule(NULL);
            $table->tinyInteger('trigger_type', false, true);
            $table->text('des');
            $table->boolean('enable');
            $table->timestamps();
        });

        Schema::table('activities', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('activities');
    }
}
