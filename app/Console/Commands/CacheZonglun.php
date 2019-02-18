<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Zonglun;
use DB;
use Cache;
class CacheZonglun extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cache:zonglun';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '缓存总论';

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
        $rs = DB::table('bihua')
                ->select('bihua')
                ->groupBy('bihua')
                ->get();
        $count = 0;
        foreach ($rs as $key => $value) {
            $where = [
                'bihua'=>$value->bihua
            ];

            $cache_key = 'zonglun_'.$value->bihua;
            $cache_key= str_replace('  ','_', $cache_key);
            $tmp = Zonglun::where($where)->get();
            Cache::forever($cache_key,json_encode($tmp));
            $count++;
            echo $count.'
            ';
        }

        echo 'done';
    }
}
