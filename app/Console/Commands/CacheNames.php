<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Xingshi;
use App\Models\Recommendname;
use Log;
use App\Services\NameTest;

class CacheNames extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'name:cache {type=1} {num=2}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

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
        $xing = Xingshi::where('id','>',0)->get()->toArray();
        $type = $this->argument('type');
        $num = $this->argument('num');
        // var_dump($type);
        // var_dump($num);
        // exit;
        $recommendnames = Recommendname::where('type',$type)->where('num',$num)->limit(10000)->get()->toArray();
        $i = 0;
        foreach($xing as $xinginfo)
        {
            //每个姓氏拼接推荐名字算分
            foreach($recommendnames as $recommendname)
            {   $i++;
                $rname = $recommendname['value1'].$recommendname['value2'];
                $name = $xinginfo['xingshi'].$rname;
                $shengri = date("Y-m-d H:i:s");
                $nameTest = new NameTest($xinginfo['xingshi'],$rname,$shengri);
                $result = $nameTest->QuickDefen();
                // var_dump($xinginfo['xingshi'].$rname);
                Log::info('name insert cache:'.$name.' id:'.$i.' score:'.$result['defen']);
            }

        }
        // dd($xing);
    }
}
