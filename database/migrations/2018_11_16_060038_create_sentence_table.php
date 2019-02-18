<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSentenceTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sentences', function (Blueprint $table) {
            $table->increments('id');
            $table->string('verse')->comment('名句内容');
            $table->string('title',150)->comment('名句对应的标题');
            $table->integer('author_id')->unsigned()->comment('作者id');
            $table->string('author')->comment('作者名字');
            $table->integer('shici_id')->unsigned()->comment('内容id');
            $table->integer('type')->default(2)->unsigned()->comment('名句生成来源类型，2诗词，3诗经，4楚辞,5元曲，6其他');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('sentences');
    }
}
