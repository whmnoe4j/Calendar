<?php

namespace App\Console\Commands;
        ini_set('memory_limit', '256M');

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;
use App\Models\KXZD;
use App\Models\XHZD;

class ImportXhzd extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:xhzd {url}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '导入新华字典';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $url = $this->argument('url');
        $contents = file_get_contents($url);
        $contents_arr = json_decode($contents,true);
        $i = 0;
        foreach ($contents_arr as $key => $value) {

            $existword = XHZD::where('word',$value['word'])->first();
            $kangxiword = KXZD::where('word',$value['word'])->first();
            $value['kxbh'] = isset($kangxiword['kxbh'])?$kangxiword['kxbh']:null;
            $value['jianti'] = $value['jian'];
            $value['fanti'] = $value['fan'];
            if(isset($existword->id)) continue;
            $kxzd = new XHZD();
            $kxzd->fill($value)->save();
            $i++;
        echo $i;

        }
    }
}
