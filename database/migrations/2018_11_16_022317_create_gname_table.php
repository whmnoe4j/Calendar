<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateGnameTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('gnames', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamps();
            $table->integer('from')->unsigned()->comment('词来源id');
            $table->integer('type')->default(1)->unsigned()->comment('名字生成来源类型，1名句，2诗词，3诗经，4楚辞,5元曲，6其他');
            $table->string('name',10)->comment('名称');
            $table->string('description')->nullable()->comment('简介');
            $table->integer('num')->default(2)->unsigned()->comment('2或者3词');
            $table->integer('loves')->default(0)->comment('收藏次数');
            $table->integer('views')->default(0)->comment('查看次数');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('gnames');
    }
}
