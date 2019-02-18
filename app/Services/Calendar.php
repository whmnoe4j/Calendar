<?php

namespace App\Services;
use App\Models\Rhesi;
use Cache;
use Log;

class Calendar
{
    protected $openid;
    protected $date;

    public function __construct($openid,$date = null,$type = 'all')
    {
        $this->openid = $openid;
        $this->date = $date;
        $this->type = $type;
    }

    public function getTips()
    {
        //openid经过MD5之后最后4位ASCII码值相加的数字为基数
        $hash = 0;
        $str = substr(md5($this->openid),-4);
        for($i = 0; $i < strlen($str); $i++)
        {
            //减掉起始位置0的ascii值
            $hash = ($hash + (ord($str[$i]) - 30));
        }

        if(is_null($this->date))
        {
             $this->date = date('Y-m-d');
        }

        //年度基数
        $year_num = (date('Y',strtotime($this->date)) - 2018) * 365;
        // 今天为本年度第几天
        $datenum = date('z',strtotime($this->date));

        $returnnum = abs( $hash + $year_num + $datenum);

         //先选取要使用的名句
        if($this->type == 'all')
        {
            $Rhesire = Rhesi::where('id','>',0)->get()->toArray();
        }else{
            $Rhesire = Rhesi::where('parent_tag_id',$this->type)->get()->toArray();
        }



        return $Rhesire[$returnnum];
    }
}
