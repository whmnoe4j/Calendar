<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateShiciTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('shicis', function (Blueprint $table) {
            $table->increments('id');
            $table->string('title',150)->comment('名句对应的标题');
            $table->integer('author_id')->unsigned()->comment('作者id');
            $table->string('dynasty',20)->comment('作者年代');
            $table->longText('content')->comment('内容');
            $table->text('translation')->comment('内容');
            $table->text('appreciation')->comment('赏析');
            $table->tinyInteger('type')->default(1)->comment('1为古词，2为诗，3为词，4为曲，5为文言文，6为其他');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('shicis');
    }
}
