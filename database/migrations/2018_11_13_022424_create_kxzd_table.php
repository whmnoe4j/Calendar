<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateKxzdTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('kxzd', function (Blueprint $table) {
            $table->increments('id');
            $table->string('word')->unique()->comment('索引');
            $table->string('jianti')->nullable()->comment('简体字形');
            $table->string('fanti')->nullable()->comment('繁体字形');
            $table->string('pinyin')->nullable()->comment('繁体字形');
            $table->string('zhuyin')->nullable()->comment('注音');
            $table->string('jianbu')->nullable()->comment('简体部首');
            $table->string('jianbi')->nullable()->comment('简体部首笔画');
            $table->string('jianzong')->nullable()->comment('简体字总笔画');
            $table->string('fanbu')->nullable()->comment('繁体部首');
            $table->string('fanbi')->nullable()->comment('繁体部首笔画');
            $table->string('fanzong')->nullable()->comment('繁体字总笔画');
            $table->string('jiankxbihua')->nullable()->comment('简体康熙笔画数');
            $table->string('fankxbihua')->nullable()->comment('繁体康熙笔画数');
            $table->string('kxbh')->nullable()->comment('康熙笔画数');
            $table->string('wb86')->nullable()->comment('五笔86');
            $table->string('wb98')->nullable()->comment('五笔98');
            $table->string('cj')->nullable()->comment('仓颉');
            $table->string('sjhm')->nullable()->comment('四角号码');
            $table->string('unicode')->nullable()->comment('unicode');
            $table->string('hanzibh')->nullable()->comment('规范汉字编号');
            $table->text('minsu')->nullable()->comment('民俗参考');
            $table->string('zixing')->nullable()->comment('字形结构');
            $table->text('kxjieshi')->nullable()->comment('康熙字典解释');
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
        Schema::dropIfExists('kxzd');
    }
}
