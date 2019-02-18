<?php

namespace App\Services;
use Cache;
use Log;
use Overtrue\ChineseCalendar\Calendar;

class FortuneTelling
{

    public function __construct()
    {
    }

    /**
     * 八字排盘
     * @return [type] [description]
     */
    public function baiZiPaiPan($sex,$birthday,$birthtime)
    {
        
        $birthday = $birthday;
        $birthtime = $birthtime;
        $birthdate = $birthday.' '.$birthtime;
        $calendar = new Calendar();
        $date_fomat = explode('-', $birthday);
        $birth = explode(":", $birthtime);
        $year = $date_fomat[0]; 
        $month = $date_fomat[1];
        $day = $date_fomat[2];
        $birth_hour = $birth[0];

        //年柱 月柱 日柱 时柱
        $result = $calendar->solar($year, $month, $day,$birth_hour); // 阳历
        $ganzhi_year = $result['ganzhi_year'];
        $ganzhi_month = $result['ganzhi_month'];
        $ganzhi_day = $result['ganzhi_day'];
        $ganzhi_hour = $result['ganzhi_hour'];

        $gan_year = mb_substr($ganzhi_year,0,1);
        $zhi_year = mb_substr($ganzhi_year,-1,1);
        $gan_month = mb_substr($ganzhi_month,0,1);
        $zhi_month = mb_substr($ganzhi_month,-1,1);
        $gan_day = mb_substr($ganzhi_day,0,1);
        $zhi_day = mb_substr($ganzhi_day,-1,1);
        $gan_hour = mb_substr($ganzhi_hour,0,1);
        $zhi_hour = mb_substr($ganzhi_hour,-1,1);

        // //时柱
        // $zhi_hour = '';
        // $shizhi_configs = config('calendar.时支');
        // foreach ($shizhi_configs as $key => $value) {
        //     $start = explode(":", $value['start']);
        //     $birth = explode(":", $birthtime);
        //     $birth_hour = $start[0];
        //     $start_hour = $start[0];

        //     if($birth_hour == $start_hour){
        //         $zhi_hour = $key;
        //         break;
        //     }
        // }
        // //日干算时干
        // $shigan_configs = config('calendar.时干');
        // $gan_hour = $shigan_configs[$gan_day][$zhi_hour];
        //十神配置
        $shishen_config = config('calendar.日干十神.'.$gan_day);
        $nayin_config = config('calendar.六十甲子.纳音');
        $changsheng_config = config('calendar.六十甲子.长生');//日干 配支
        

        //当月节气
        $now_jie_day = $calendar->getTerm($year, ($month * 2 - 1)); // 返回当月「节气」为几日开始
        $jieqi_calendar = $calendar->solar($year, $month,$now_jie_day,$birth_hour);
        $jieqi_config = ['小寒', '大寒', '立春', '雨水', '惊蛰', '春分',
        '清明', '谷雨', '立夏', '小满', '芒种', '夏至',
        '小暑', '大暑', '立秋', '处暑', '白露', '秋分',
        '寒露', '霜降', '立冬', '小雪', '大雪', '冬至'];
        $rs = array_search($jieqi_calendar['term'],$jieqi_config);
        $jieqi_date = $this->getTerm($year,array_search($jieqi_calendar['term'],$jieqi_config));

        //大运 
        if($year%2 == 0){
            if($sex == 1){//男
                $shunni = 1;
            }else{//女
                //逆数
                $shunni = 2;
            }
        }else{
            if($sex == 1){//男
                $shunni = 2;
            }else{//女
                $shunni = 1;
            }
        }
        
        $dayun = $this->getDayun($shunni,$birthdate,$jieqi_date,$year,$month,$changsheng_config,$nayin_config,$shishen_config,$calendar,$birth_hour,$gan_day,$jieqi_config,$ganzhi_month);
        $dayun_ganzhi = $dayun;
        $dayun_ganzhi['jieqi']['date'] = $jieqi_date;
        $dayun_ganzhi['jieqi']['name'] = $jieqi_calendar['term'];
        
        //地支藏干
        $canggan_year = $this->getCanggan($zhi_year,$gan_day);
        $canggan_month = $this->getCanggan($zhi_month,$gan_day);
        $canggan_day = $this->getCanggan($zhi_day,$gan_day);
        $canggan_hour = $this->getCanggan($zhi_hour,$gan_day);

        $dayun_ganzhi['qiankun'] = [
            'nian'=>['ganzhi'=>$ganzhi_year,'shishen'=>$shishen_config[$gan_year],'nayin'=>$nayin_config[$ganzhi_year],'changsheng'=>$changsheng_config[$gan_day.$zhi_year],'canggan'=>$canggan_year],
            'month'=>['ganzhi'=>$ganzhi_month,'shishen'=>$shishen_config[$gan_month],'nayin'=>$nayin_config[$ganzhi_month],'changsheng'=>$changsheng_config[$gan_day.$zhi_month],'canggan'=>$canggan_month],
            'day'=>['ganzhi'=>$ganzhi_day,'shishen'=>'日主','nayin'=>$nayin_config[$ganzhi_day],'changsheng'=>$changsheng_config[$gan_day.$zhi_day],'canggan'=>$canggan_day],
            'hour'=>['ganzhi'=>$ganzhi_hour,'shishen'=>$shishen_config[$gan_hour],'nayin'=>$nayin_config[$ganzhi_hour],'changsheng'=>$changsheng_config[$gan_day.$zhi_hour],'canggan'=>$canggan_hour]
        ] ;

        //神煞
        $bazi = [
            'gan_year'=>$gan_year,
            'zhi_year'=>$zhi_year,
            'gan_month'=>$gan_month,
            'zhi_month'=>$zhi_month,
            'gan_day'=>$gan_day,
            'zhi_day'=>$zhi_day,
            'gan_hour'=>$gan_hour,
            'zhi_hour'=>$zhi_hour
        ];
        $shensha = $this->getShenSha($bazi,$result['lunar_month_chinese'],$year,$month,$day);

        //流年 1-80岁  小运
        //以生年干支为0岁，再顺行排列为1岁、2岁、3岁等，不分男女或年柱阴阳。比如甲申年出生的男女命。0岁的干支为甲申、1岁的干支为乙酉、2岁的干支为丙戌，其它的流年干支按六十花甲子的顺序依次顺推即可。 
        $liunian = [];
        $ganzhi_config = config('calendar.六十甲子.甲子');
        $ganzhi_key = array_search($gan_year.$zhi_year,$ganzhi_config);
        $xiaoyun = [];
        if($shunni == 1){//顺
            $xiaoyun_ganzhi_config = $ganzhi_config;
        }else{//逆
            $xiaoyun_ganzhi_config = array_reverse($ganzhi_config);
        }
        $xiaoyun_ganzhi_key = array_search($ganzhi_hour,$xiaoyun_ganzhi_config);
        for ($i=1; $i <=80+$dayun['qiyun']['year'] ; $i++) { 
            //流年
            $tmp_key = ($ganzhi_key+$i)%60;
            $liunian[] = ['ganzhi'=>$ganzhi_config[$tmp_key],'year'=>$year+$i,'age'=>$i];

            //小运
            $tmp_xiaoyun_key = ($xiaoyun_ganzhi_key+$i)%60;
            $tmp_ganzhi = $xiaoyun_ganzhi_config[$tmp_xiaoyun_key];
            $tmp_gan = mb_substr($tmp_ganzhi,0,1);
            $tmp_zhi = mb_substr($tmp_ganzhi,1,1);
            //长生
            $changsheng = $changsheng_config[$gan_day.$tmp_zhi];
            $xiaoyun[] = ['ganzhi'=>$tmp_ganzhi,'shishen'=>$shishen_config[$tmp_gan],'changsheng'=>$changsheng,'age'=>$i];
        }
       return ['dayun'=>$dayun_ganzhi,'shensha'=>$shensha,'liunian'=>$liunian,'xiaoyun'=>$xiaoyun];
    }

    /**
     * 获取大运
     */
    public function getDayun($shunni,$birthdate,$jieqi_date,$year,$month,$changsheng_config,$nayin_config,$shishen_config,$calendar,$birth_hour,$gan_day,$jieqi_config,$ganzhi_month)
    {
        $birthdate_time = strtotime($birthdate);
        $jieqi_date_time = strtotime($jieqi_date);
        $dayun = [];
        if($shunni == 1){//顺数

            //相差时间戳
            if($birthdate_time == $jieqi_date_time){
                $diff_time = 0;
            }elseif($birthdate_time < $jieqi_date_time){
                $diff_time = $jieqi_date_time - $birthdate_time;
            }else{
                $next_month = $month+1;
                $next_year = $year;
                if($next_month == 13){
                    $next_month = 1;
                    $next_year = $year + 1;
                }

                $next_jie_day = $calendar->getTerm($next_year, ($next_month * 2 - 1));
                $jieqi_calendar = $calendar->solar($next_year, $next_month,$next_jie_day,$birth_hour);
                $jieqi_date = $this->getTerm($next_year,array_search($jieqi_calendar['term'],$jieqi_config));
                $jieqi_date_time = strtotime($jieqi_date);
                $diff_time =$jieqi_date_time - $birthdate_time;
            }

            //甲子
            $jiazi_config = config('calendar.六十甲子.甲子');//日干 配支
            $ganzhi_month_key = array_search($ganzhi_month,$jiazi_config);
        }else{
            //相差时间戳
            if($birthdate_time == $jieqi_date_time){
                    $diff_time = 0;
            }elseif($birthdate_time > $jieqi_date_time){
                $diff_time = $birthdate_time - $jieqi_date_time;
            }else{
                $last_month = $month-1;
                $last_year = $year;
                if($last_month == 0){
                    $last_month = 12;
                    $last_year = $year - 1;
                }

                $last_jie_day = $calendar->getTerm($last_year, ($last_month * 2 - 1));
                $jieqi_calendar = $calendar->solar($last_year, $last_month,$last_jie_day,$birth_hour);
                $jieqi_date = $this->getTerm($last_year,array_search($jieqi_calendar['term'],$jieqi_config));
                $jieqi_date_time = strtotime($jieqi_date);
                $diff_time =$birthdate_time-$jieqi_date_time;
            }
            $jiazi_config = array_reverse(config('calendar.六十甲子.甲子'));//日干 配支
            $ganzhi_month_key = array_search($ganzhi_month,$jiazi_config);
        }
        

        $qiyun_hour = $diff_time/3600;//一共差了多少小时
        $qiyun_day = floor($qiyun_hour/24);//一共差了多少天
        $diff_hour = $qiyun_hour%24;//剩下多少小时
        $qiyun_diff_year = floor($qiyun_day/3);//一共差了几岁
        $diff_day = $qiyun_day%3;//剩下几天
        $qiyun_diff_month = $diff_day*4;//差了几个月
        $qiyun_diff_day =$diff_hour*5;//差了几天

        if($qiyun_diff_day>30){
            $qiyun_diff_month = $qiyun_diff_month + floor($qiyun_diff_day/30);
            $qiyun_diff_day = $qiyun_diff_day%30;
        }

        if($qiyun_diff_month>0 || $qiyun_diff_day>0){
            $qiyun_nianling = $qiyun_diff_year+1;
        }else{
            $qiyun_nianling = $qiyun_diff_year;
        }
        for ($i=1; $i <= 8; $i++) { 
            $tmp_ganzhi_month_key = ($ganzhi_month_key + $i)%60;
            $ganzhi = $jiazi_config[$tmp_ganzhi_month_key];
            $tmp_gan = mb_substr($ganzhi,0,1);
            $tmp_zhi = mb_substr($ganzhi,1,1);
            $now_qiyun_nianling = $qiyun_nianling + ($i-1)*10;
            //长生
            $changsheng = $changsheng_config[$gan_day.$tmp_zhi];
            //纳音
            $nayin = $nayin_config[$ganzhi];

            $dayun[] = ['ganzhi'=>$ganzhi,'shishen'=>$shishen_config[$tmp_gan],'nayin'=>$nayin,'changsheng'=>$changsheng,'qiyun_nianling'=>$now_qiyun_nianling];
        }
        $qiyun['year'] = $qiyun_diff_year;
        $qiyun['month'] = $qiyun_diff_month;
        $qiyun['day'] = $qiyun_diff_day;
        $qiyun_time = strtotime("+$qiyun_diff_year year",$birthdate_time);
        $qiyun_time = strtotime("+$qiyun_diff_month month",$qiyun_time);
        $qiyun_time = strtotime("+$qiyun_diff_day day",$qiyun_time);
        $qiyun_date = date('Y-m-d H:i:s',$qiyun_time);
        $qiyun['date'] = $qiyun_date;
        return ['dayun'=>$dayun,'qiyun'=>$qiyun];
    }

    /**
     *获取各支藏干 
     */
    public function getCanggan($zhi,$gan_day)
    {
        $canggan_config = config('calendar.地支藏干');//日干 配支
        $shishen_config = config('calendar.日干十神.'.$gan_day);//十神配置

        $canggans = $canggan_config[$zhi];
        $return = [];
        foreach ($canggans as $key => $value) {
            //十神
            $shen = $shishen_config[$value];
            $return[]= ['canggan'=>$value,'shishen'=>$shen];
        }
        return $return;
    }

    /**
     * 获取节气的具体时间
     * @param  [type] $y [年份]
     * @param  [type] $n [第n个节气]
     * @return [type]    [description]
     */
    public function getTerm($y,$n)
    {
        $termInfo = [0, 21208, 42467, 63836, 85337, 107014, 128867, 150921, 173149, 195551, 218072, 240693, 263343, 285989, 308563, 331033, 353350, 375494, 397447, 419210, 440795, 462224, 483532, 504758];
        $time = ((31556925974.7 * ($y - 1890) + $termInfo[$n] * 60000) + strtotime('1890-01-05 16:02:31')*1000)/1000;
        $date = date('Y-m-d H:i:s',$time);
        return $date ;

    }

    /**
     * 根据四柱获取神煞
     */
    public function getShenSha($bazi,$lunar_month,$year,$month,$day)
    {
        $gan_year = $bazi['gan_year'];
        $gan_month = $bazi['gan_month'];
        $gan_day = $bazi['gan_day'];
        $gan_hour = $bazi['gan_hour'];
        $zhi_year = $bazi['zhi_year'];
        $zhi_month = $bazi['zhi_month'];
        $zhi_day = $bazi['zhi_day'];
        $zhi_hour = $bazi['zhi_hour'];

        $nianzhu = [];
        $yuezhu = [];
        $rizhu = [];
        $shizhu = [];

        $shensha_config = config('calendar.神煞');
        foreach ($shensha_config as $key => $value) {
            switch ($key) {
                case '天乙贵人':
                    //甲戊并牛羊，乙己鼠猴乡，丙丁猪鸡位，壬癸兔蛇藏，庚辛逢虎马，此是贵人方。 查法：以日干起贵人，地支见者为是
                    if(in_array($gan_day.$zhi_year,$value['chafa'])) $nianzhu[] = $key;
                    if(in_array($gan_day.$zhi_month,$value['chafa'])) $yuezhu[] = $key;
                    if(in_array($gan_day.$zhi_day,$value['chafa'])) $rizhu[] = $key;
                    if(in_array($gan_day.$zhi_hour,$value['chafa'])) $shizhu[] = $key;
                    break;
                case '太极贵人':
                    //甲乙生人子午中，丙丁鸡兔定亨通，戊己两干临四季，庚辛寅亥禄丰隆，壬癸巳申偏喜美 查法：同天乙贵人
                    if(in_array($gan_day.$zhi_year,$value['chafa'])) $nianzhu[] = $key;
                    if(in_array($gan_day.$zhi_month,$value['chafa'])) $yuezhu[] = $key;
                    if(in_array($gan_day.$zhi_day,$value['chafa'])) $rizhu[] = $key;
                    if(in_array($gan_day.$zhi_hour,$value['chafa'])) $shizhu[] = $key;
                    break;
                case '天德贵人':
                    //正月生者见丁，二月生者见申，三月生者见壬，四月生者见辛，五月生者见亥，六月生者见甲，七月生者见癸，八月生者见寅，九月生者见丙，十月生者见乙，十一月生者见巳，十二月生者见庚。凡四柱年月日时上见者为有天德贵人。
                    if(in_array($lunar_month.$zhi_year,$value['chafa'])) $nianzhu[] = $key;
                    if(in_array($lunar_month.$gan_year,$value['chafa'])) $nianzhu[] = $key;
                    if(in_array($lunar_month.$zhi_month,$value['chafa'])) $yuezhu[] = $key;
                    if(in_array($lunar_month.$gan_month,$value['chafa'])) $yuezhu[] = $key;
                    if(in_array($lunar_month.$zhi_day,$value['chafa'])) $rizhu[] = $key;
                    if(in_array($lunar_month.$gan_day,$value['chafa'])) $rizhu[] = $key;
                    if(in_array($lunar_month.$zhi_hour,$value['chafa'])) $shizhu[] = $key;
                    if(in_array($lunar_month.$gan_hour,$value['chafa'])) $shizhu[] = $key;
                    break;
                case '月德贵人':
                    //寅午戌月生者见丙，申子辰月生者见壬，亥卯未月生者见甲，巳酉丑月生者见庚。凡柱中年月日时干上见者为有月德贵人。
                    if(in_array($zhi_month.$gan_year,$value['chafa'])) $nianzhu[] = $key;
                    if(in_array($zhi_month.$gan_month,$value['chafa'])) $yuezhu[] = $key;
                    if(in_array($zhi_month.$gan_day,$value['chafa'])) $rizhu[] = $key;
                    if(in_array($zhi_month.$gan_hour,$value['chafa'])) $shizhu[] = $key;
                    break;
                case '三奇贵人':
                    //天干有乙丙丁或地支有卯巳午，顺行为妙
                    if(in_array($gan_year.$gan_month.$gan_day,$value['chafa']) || in_array($zhi_year.$zhi_month.$zhi_day,$value['chafa'])){
                        $nianzhu[] = $key;
                        $yuezhu[] = $key;
                        $rizhu[] = $key;
                    };

                    if(in_array($gan_month.$gan_day.$gan_hour,$value['chafa']) || in_array($zhi_month.$zhi_day.$zhi_hour,$value['chafa'])){
                        $nianzhu[] = $key;
                        $yuezhu[] = $key;
                        $rizhu[] = $key;
                    } 
                    break;
                case '福星贵人':
                    //甲丙相邀入虎乡，更游鼠穴最高强，戊猴己未丁宜亥，乙癸逢牛卯禄昌，庚赶马头辛到巳，壬骑龙背喜非常，此为有福文昌贵，遇者应知受宠光。查法：以年干或日干为主。凡甲丙两干见寅或子，乙癸两干见卯或丑，戊干见申，己干见未，丁干见亥，庚干见午，辛干见巳，壬干见辰是也
                    //年干
                    if(in_array($gan_year.$zhi_year,$value['chafa'])) $nianzhu[] = $key;
                    if(in_array($gan_year.$zhi_month,$value['chafa'])) $yuezhu[] = $key;
                    if(in_array($gan_year.$zhi_day,$value['chafa'])) $rizhu[] = $key;
                    if(in_array($gan_year.$zhi_hour,$value['chafa'])) $shizhu[] = $key;
                    //日干
                    if(in_array($gan_day.$zhi_year,$value['chafa'])) $nianzhu[] = $key;
                    if(in_array($gan_day.$zhi_month,$value['chafa'])) $yuezhu[] = $key;
                    if(in_array($gan_day.$zhi_day,$value['chafa'])) $rizhu[] = $key;
                    if(in_array($gan_day.$zhi_hour,$value['chafa'])) $shizhu[] = $key;
                    break;
                case '文昌贵人':
                    //甲乙巳午报君知，丙戊申宫丁己鸡。庚猪辛鼠壬逢虎，癸人见卯入云梯。查法：以年干或日干为主，凡四柱中地支所见者为是。
                    //年干
                    if(in_array($gan_year.$zhi_year,$value['chafa'])) $nianzhu[] = $key;
                    if(in_array($gan_year.$zhi_month,$value['chafa'])) $yuezhu[] = $key;
                    if(in_array($gan_year.$zhi_day,$value['chafa'])) $rizhu[] = $key;
                    if(in_array($gan_year.$zhi_hour,$value['chafa'])) $shizhu[] = $key;
                    //日干
                    if(in_array($gan_day.$zhi_year,$value['chafa'])) $nianzhu[] = $key;
                    if(in_array($gan_day.$zhi_month,$value['chafa'])) $yuezhu[] = $key;
                    if(in_array($gan_day.$zhi_day,$value['chafa'])) $rizhu[] = $key;
                    if(in_array($gan_day.$zhi_hour,$value['chafa'])) $shizhu[] = $key;
                    break;
                case '魁罡贵人':
                    //壬辰庚戌与庚辰，戊戌魁罡四座神，不见财官刑煞并，身行旺地贵无伦。查法：日柱见者为是
                    if(in_array($gan_day.$zhi_day.$zhi_day,$value['chafa'])) $rizhu[] = $key;
                    break;
                case '国印贵人':
                    //甲见戌，乙见亥，丙见丑，丁见寅，戊见丑，己见寅，庚见辰，辛见巳。壬见未，癸见申。查法：以年干或日干为主，地支见者为是
                    //年干
                    if(in_array($gan_year.$zhi_year,$value['chafa'])) $nianzhu[] = $key;
                    if(in_array($gan_year.$zhi_month,$value['chafa'])) $yuezhu[] = $key;
                    if(in_array($gan_year.$zhi_day,$value['chafa'])) $rizhu[] = $key;
                    if(in_array($gan_year.$zhi_hour,$value['chafa'])) $shizhu[] = $key;
                    //日干
                    if(in_array($gan_day.$zhi_year,$value['chafa'])) $nianzhu[] = $key;
                    if(in_array($gan_day.$zhi_month,$value['chafa'])) $yuezhu[] = $key;
                    if(in_array($gan_day.$zhi_day,$value['chafa'])) $rizhu[] = $key;
                    if(in_array($gan_day.$zhi_hour,$value['chafa'])) $shizhu[] = $key;
                    break;
                case '学堂':
                    //学堂：金命见巳，辛巳为正；木命见亥，己亥为正；水命见申，甲申为正；土命见申，戊申为正；火命见寅，丙寅为正 学堂词馆查法，均以年干或日干为主，柱中地支临之为是
                    //年干
                    if(in_array($gan_year.$zhi_year,$value['chafa'])) $nianzhu[] = $key;
                    if(in_array($gan_year.$zhi_month,$value['chafa'])) $yuezhu[] = $key;
                    if(in_array($gan_year.$zhi_day,$value['chafa'])) $rizhu[] = $key;
                    if(in_array($gan_year.$zhi_hour,$value['chafa'])) $shizhu[] = $key;
                    //日干
                    if(in_array($gan_day.$zhi_year,$value['chafa'])) $nianzhu[] = $key;
                    if(in_array($gan_day.$zhi_month,$value['chafa'])) $yuezhu[] = $key;
                    if(in_array($gan_day.$zhi_day,$value['chafa'])) $rizhu[] = $key;
                    if(in_array($gan_day.$zhi_hour,$value['chafa'])) $shizhu[] = $key;
                    break;
                case '词馆':  
                    //词馆：甲干见庚寅，乙干见辛卯，丙干见乙巳，丁干见戊午，戊干见丁巳，己干见庚午，庚干见壬申，辛干见癸酉，壬干见癸亥，癸干见壬戌  学堂词馆查法，均以年干或日干为主，柱中地支临之为是
                    //年干
                    if(in_array($gan_year.$gan_year.$zhi_year,$value['chafa'])) $nianzhu[] = $key;
                    if(in_array($gan_year.$gan_month.$zhi_month,$value['chafa'])) $yuezhu[] = $key;
                    if(in_array($gan_year.$gan_day.$zhi_day,$value['chafa'])) $rizhu[] = $key;
                    if(in_array($gan_year.$gan_hour.$zhi_hour,$value['chafa'])) $shizhu[] = $key;
                    //日干
                    if(in_array($gan_day.$gan_year.$zhi_year,$value['chafa'])) $nianzhu[] = $key;
                    if(in_array($gan_day.$gan_month.$zhi_month,$value['chafa'])) $yuezhu[] = $key;
                    if(in_array($gan_day.$gan_day.$zhi_day,$value['chafa'])) $rizhu[] = $key;
                    if(in_array($gan_day.$gan_hour.$zhi_hour,$value['chafa'])) $shizhu[] = $key;
                    break;
                case '德秀贵人':
                    //寅午戌月，丙丁为德，戊癸为秀。申子辰月，壬癸戊己为德，丙辛甲己为秀。巳酉丑月，庚辛为德，乙庚为秀。亥卯未月，甲乙为德，丁壬为秀。德秀之查法，以生月为主，看四柱天干中有否。如寅午戌月生的人，柱中天干见壬癸之，柱中再有丙丁其中之一者，为德秀
                    $de = 0;
                    $xiu = 0;
                    $nzhu = 0;
                    $yzhu = 0;
                    $dzhu = 0;
                    $hzhu = 0;
                    if(in_array($zhi_month.$gan_year, $value['德']['chafa'])){
                        $de++;
                        $nzhu++;
                    } 
                    if(in_array($zhi_month.$gan_month, $value['德']['chafa'])){
                        $de++; 
                        $yzhu++;
                    } 
                    if(in_array($zhi_month.$gan_day, $value['德']['chafa'])){
                        $de++;
                        $dzhu++;  
                    } 
                    if(in_array($zhi_month.$gan_hour, $value['德']['chafa'])){
                        $de++;
                        $hzhu++;
                    } 

                    if(in_array($zhi_month.$gan_year, $value['秀']['chafa'])){
                        $de++;
                        $nzhu++;
                    } 
                    if(in_array($zhi_month.$gan_month, $value['秀']['chafa'])){
                        $de++; 
                        $yzhu++;
                    } 
                    if(in_array($zhi_month.$gan_day, $value['秀']['chafa'])){
                        $de++;
                        $dzhu++;  
                    } 
                    if(in_array($zhi_month.$gan_hour, $value['秀']['chafa'])){
                        $de++;
                        $hzhu++;
                    }
                    if($de>0 && $xiu>0){
                        if($nzhu > 0) $nianzhu[] = $key;
                        if($yzhu > 0) $yuezhu[] = $key;
                        if($dzhu > 0) $rizhu[] = $key;
                        if($hzhu > 0) $shizhu[] = $key;
                    } 
                    break;
                case '驿马':
                    //申子辰马在寅，寅午戌马在申，巳酉丑马在亥，亥卯未马在巳。查法：以年支或日支为主，看四柱中何地支临之则为马星
                    //年支
                    if(in_array($zhi_year.$zhi_year, $value['chafa'])) $nianzhu[] = $key;
                    if(in_array($zhi_year.$zhi_month, $value['chafa'])) $yuezhu[] = $key;
                    if(in_array($zhi_year.$zhi_day, $value['chafa'])) $rizhu[] = $key;
                    if(in_array($zhi_year.$zhi_hour, $value['chafa'])) $shizhu[] = $key;
                    //日支
                    if(in_array($zhi_day.$zhi_year, $value['chafa'])) $nianzhu[] = $key;
                    if(in_array($zhi_day.$zhi_month, $value['chafa'])) $yuezhu[] = $key;
                    if(in_array($zhi_day.$zhi_day, $value['chafa'])) $rizhu[] = $key;
                    if(in_array($zhi_day.$zhi_hour, $value['chafa'])) $shizhu[] = $key;
                    break;
                case '华盖':
                    //寅午戌见戌，亥卯未见未，申子辰见辰，巳酉丑见丑。查法：以年支或日支为主，凡四柱中所见者为有华盖星
                    //年支
                    if(in_array($zhi_year.$zhi_year, $value['chafa'])) $nianzhu[] = $key;
                    if(in_array($zhi_year.$zhi_month, $value['chafa'])) $yuezhu[] = $key;
                    if(in_array($zhi_year.$zhi_day, $value['chafa'])) $rizhu[] = $key;
                    if(in_array($zhi_year.$zhi_hour, $value['chafa'])) $shizhu[] = $key;
                    //日支
                    if(in_array($zhi_day.$zhi_year, $value['chafa'])) $nianzhu[] = $key;
                    if(in_array($zhi_day.$zhi_month, $value['chafa'])) $yuezhu[] = $key;
                    if(in_array($zhi_day.$zhi_day, $value['chafa'])) $rizhu[] = $key;
                    if(in_array($zhi_day.$zhi_hour, $value['chafa'])) $shizhu[] = $key;
                    break;
                case '将星':
                    //寅午戌见午，巳酉丑见酉，申子辰见子，亥卯未见卯。查法：以年支或日支查其余各支，见者为将星.
                    //年支
                    if(in_array($zhi_year.$zhi_year, $value['chafa'])) $nianzhu[] = $key;
                    if(in_array($zhi_year.$zhi_month, $value['chafa'])) $yuezhu[] = $key;
                    if(in_array($zhi_year.$zhi_day, $value['chafa'])) $rizhu[] = $key;
                    if(in_array($zhi_year.$zhi_hour, $value['chafa'])) $shizhu[] = $key;
                    //日支
                    if(in_array($zhi_day.$zhi_year, $value['chafa'])) $nianzhu[] = $key;
                    if(in_array($zhi_day.$zhi_month, $value['chafa'])) $yuezhu[] = $key;
                    if(in_array($zhi_day.$zhi_day, $value['chafa'])) $rizhu[] = $key;
                    if(in_array($zhi_day.$zhi_hour, $value['chafa'])) $shizhu[] = $key;
                    break;
                case '金舆':
                    //甲龙乙蛇丙戊羊，丁己猴歌庚犬方，辛猪壬牛癸逢虎，凡人遇此福气昌。查法：以日干为主，四支见者为是
                    if(in_array($gan_day.$zhi_year,$value['chafa'])) $nianzhu[] = $key;
                    if(in_array($gan_day.$zhi_month,$value['chafa'])) $yuezhu[] = $key;
                    if(in_array($gan_day.$zhi_day,$value['chafa'])) $rizhu[] = $key;
                    if(in_array($gan_day.$zhi_hour,$value['chafa'])) $shizhu[] = $key;
                    break;
                case '金神':
                    //金神者，乙丑，己巳，癸酉三组干支。查法：日柱或时柱见者为是
                    if(in_array($gan_day.$zhi_day,$value['chafa'])) $rizhu[] = $key;
                    if(in_array($gan_hour.$zhi_hour,$value['chafa'])) $shizhu[] = $key;
                    break;
                case '天医':
                    //正月生见丑，二月生见寅，三月生见卯，四月生见辰，五月生见巳，六月生见午，七月生见未，八月生见申，九月生见酉，十月生见戌，十一月生见亥，十二月生见子。查法：以月支查其它地支，见者为是。
                    if(in_array($zhi_month.$zhi_year,$value['chafa'])) $nianzhu[] = $key;
                    if(in_array($zhi_month.$zhi_month,$value['chafa'])) $yuezhu[] = $key;
                    if(in_array($zhi_month.$zhi_day,$value['chafa'])) $rizhu[] = $key;
                    if(in_array($zhi_month.$zhi_hour,$value['chafa'])) $shizhu[] = $key;
                    break;
                case '禄神':
                    //甲禄在寅，乙禄在卯，丙戊禄在巳，丁己禄在午，庚禄在申，辛禄在酉，壬禄在亥，癸禄在子。查法：以日干查四支，见之者为是。禄在年支叫岁禄，禄在月支叫建禄，禄在日支叫专禄，禄在时支叫归禄
                    if(in_array($gan_day.$zhi_year,$value['chafa'])) $nianzhu[] = $key;
                    if(in_array($gan_day.$zhi_month,$value['chafa'])) $yuezhu[] = $key;
                    if(in_array($gan_day.$zhi_day,$value['chafa'])) $rizhu[] = $key;
                    if(in_array($gan_day.$zhi_hour,$value['chafa'])) $shizhu[] = $key;
                    break; 
                case '天罗地网':
                    //辰为天罗，戌为地网。火命人逢戌亥为天罗，水土命逢辰巳为地网。辰见巳，巳见辰为地网；戌见亥，亥见戌为天罗。男忌天罗，女忌地网。查法：以年支或日支为主，其它地支见之者为是
                    $ming = config('calendar.六十甲子.纳音.'.$gan_year.$zhi_year);//命属
                    $ming = mb_substr($ming,-1,1);
                    //火命
                    if($ming == '火'){
                        if(in_array($zhi_year.$zhi_year,$value['天罗']['chafa'])) $nianzhu[] = '天罗';
                        if(in_array($zhi_year.$zhi_month,$value['天罗']['chafa'])) $yuezhu[] = '天罗';
                        if(in_array($zhi_year.$zhi_day,$value['天罗']['chafa'])) $rizhu[] = '天罗';
                        if(in_array($zhi_year.$zhi_hour,$value['天罗']['chafa'])) $shizhu[] = '天罗';

                        if(in_array($zhi_day.$zhi_year,$value['天罗']['chafa'])) $nianzhu[] = '天罗';
                        if(in_array($zhi_day.$zhi_month,$value['天罗']['chafa'])) $yuezhu[] = '天罗';
                        if(in_array($zhi_day.$zhi_day,$value['天罗']['chafa'])) $rizhu[] = '天罗';
                        if(in_array($zhi_day.$zhi_hour,$value['天罗']['chafa'])) $shizhu[] = '天罗';
                    }
                    
                    //水土命
                    if($ming == '水' || $ming == '土'){
                        if(in_array($zhi_year.$zhi_year,$value['地网']['chafa'])) $nianzhu[] = '地网';
                        if(in_array($zhi_year.$zhi_month,$value['地网']['chafa'])) $yuezhu[] = '地网';
                        if(in_array($zhi_year.$zhi_day,$value['地网']['chafa'])) $rizhu[] = '地网';
                        if(in_array($zhi_year.$zhi_hour,$value['地网']['chafa'])) $shizhu[] = '地网';

                        if(in_array($zhi_day.$zhi_year,$value['地网']['chafa'])) $nianzhu[] = '地网';
                        if(in_array($zhi_day.$zhi_month,$value['地网']['chafa'])) $yuezhu[] = '地网';
                        if(in_array($zhi_day.$zhi_day,$value['地网']['chafa'])) $rizhu[] = '地网';
                        if(in_array($zhi_day.$zhi_hour,$value['地网']['chafa'])) $shizhu[] = '地网';
                    }
                    break;
                case '羊刃':
                    //甲羊刃在卯，乙羊刃在寅，丙戊羊刃在午，丁己羊刃在巳，庚羊刃在酉，辛羊刃在申，壬羊刃在子，癸羊刃在亥。查法：以日干为主，四支见之者为是
                    if(in_array($gan_day.$zhi_year,$value['chafa'])) $nianzhu[] = $key;
                    if(in_array($gan_day.$zhi_month,$value['chafa'])) $yuezhu[] = $key;
                    if(in_array($gan_day.$zhi_day,$value['chafa'])) $rizhu[] = $key;
                    if(in_array($gan_day.$zhi_hour,$value['chafa'])) $shizhu[] = $key;
                    break;
                case '亡神':
                    //寅午戌见巳，亥卯未见寅，巳酉丑见申，申子辰见亥。查法：以年支或日支为主，四柱取三合局为用，无合局不可用。
                    if(in_array($zhi_year.$zhi_year, $value['chafa'])) $nianzhu[] = $key;
                    if(in_array($zhi_year.$zhi_month, $value['chafa'])) $yuezhu[] = $key;
                    if(in_array($zhi_year.$zhi_day, $value['chafa'])) $rizhu[] = $key;
                    if(in_array($zhi_year.$zhi_hour, $value['chafa'])) $shizhu[] = $key;
                    //日支
                    if(in_array($zhi_day.$zhi_year, $value['chafa'])) $nianzhu[] = $key;
                    if(in_array($zhi_day.$zhi_month, $value['chafa'])) $yuezhu[] = $key;
                    if(in_array($zhi_day.$zhi_day, $value['chafa'])) $rizhu[] = $key;
                    if(in_array($zhi_day.$zhi_hour, $value['chafa'])) $shizhu[] = $key;
                    break;
                case '空亡':
                    //甲子---乙丑---丙寅---丁卯---戊辰---己巳---庚午---辛未---壬申---癸酉 (遇)戌 亥
                    // 甲戌---乙亥---丙子---丁丑---戊寅---己卯---庚辰---辛巳---壬午---癸未 (遇)申 酉
                    // 甲申---乙酉---丙戌---丁亥---戊子---己丑---庚寅---辛卯---壬辰---癸巳 (遇)午 未
                    // 甲午---乙未---丙申---丁酉---戊戌---己亥---庚子---辛丑---壬寅---癸卯 (遇)辰 巳
                    // 甲辰---乙巳---丙午---丁未---戊申---己酉---庚戌---辛亥---壬子---癸丑 (遇)寅 卯
                    // 甲寅---乙卯---丙辰---丁巳---戊午---己未---庚申---辛酉---壬戌---癸亥 (遇)子 丑
                    // 查法: 以日柱为主, 柱中年、 月、 时支见者为空亡。
                    
                    if(in_array($gan_day.$zhi_day.$zhi_year, $value['chafa'])) $nianzhu[] = $key;
                    if(in_array($gan_day.$zhi_day.$zhi_month, $value['chafa'])) $yuezhu[] = $key;
                    if(in_array($gan_day.$zhi_day.$zhi_hour, $value['chafa'])) $shizhu[] = $key;
                    break;
                case '咸池':
                    //申子辰在酉，寅午戌在卯，巳酉丑在午，亥卯未在子。查法：以年支或日支查四柱其它地支，见者为是。
                    if(in_array($zhi_year.$zhi_year, $value['chafa'])) $nianzhu[] = $key;
                    if(in_array($zhi_year.$zhi_month, $value['chafa'])) $yuezhu[] = $key;
                    if(in_array($zhi_year.$zhi_day, $value['chafa'])) $rizhu[] = $key;
                    if(in_array($zhi_year.$zhi_hour, $value['chafa'])) $shizhu[] = $key;
                    //日支
                    if(in_array($zhi_day.$zhi_year, $value['chafa'])) $nianzhu[] = $key;
                    if(in_array($zhi_day.$zhi_month, $value['chafa'])) $yuezhu[] = $key;
                    if(in_array($zhi_day.$zhi_day, $value['chafa'])) $rizhu[] = $key;
                    if(in_array($zhi_day.$zhi_hour, $value['chafa'])) $shizhu[] = $key;
                    break;
                case '孤鸾煞':
                    //乙巳，丁巳，辛亥，戊申，壬寅，戊午，壬子，丙午。查法：四柱日时同时出现以上任何两组者为是。
                    $ri_zhu = $gan_day.$zhi_day;
                    $shi_zhu = $gan_hour.$zhi_hour;
                    if(in_array($ri_zhu, $value['chafa']) && in_array($shi_zhu,$value['chafa']) && $ri_zhu != $shi_zhu){
                        $rizhu[] = $key;
                        $shizhu[] = $key;
                    }
                    break;
                case '四废':
                    //春庚申，辛酉，夏壬子，癸亥，秋甲寅，乙卯，冬丙午，丁巳。查法：凡四柱日干支生于该季为是。
                    //2月3  5月5  8月7  11月7
                    $chun_time = strtotime($year.'-02-03');
                    $xia_time = strtotime($year.'-05-05');
                    $qiu_time = strtotime($year.'-08-07');
                    $dong_time = strtotime($year.'-11-07');

                    $birthtime = strtotime($year.'-'.$month.'-'.$day);
                    if($birthtime<$chun_time || $birthtime >= $dong_time){
                        //冬天
                        if(in_array($gan_day.$zhi_day, $value['冬']['chafa'])) $rizhu[] = $key;
                    }

                    if($birthtime>= $chun_time && $birthtime <$xia_time ){
                        //春天
                        if(in_array($gan_day.$zhi_day, $value['春']['chafa'])) $rizhu[] = $key;
                    }

                    if($birthtime>=$xia_time && $birthtime<$qiu_time){
                        //夏天
                        if(in_array($gan_day.$zhi_day, $value['夏']['chafa'])) $rizhu[] = $key;
                    }

                    if($birthtime >= $qiu_time && $birthtime<$dong_time){
                        //秋天
                        if(in_array($gan_day.$zhi_day, $value['秋']['chafa'])) $rizhu[] = $key;
                    }   
                    break;  
                case '阴阳差错':
                    //丙子，丁丑，戊寅，辛卯，壬辰，癸巳，丙午，丁未，戊申，辛酉，壬戌，癸亥。查法：日柱见者为是。
                    if(in_array($gan_day.$zhi_day, $value['chafa'])) $rizhu[] = $key;
                    break;
                default:
                    # code...
                    break;
            }
        }
        $nianzhu = array_unique($nianzhu);
        $yuezhu = array_unique($yuezhu);
        $rizhu = array_unique($rizhu);
        $shizhu = array_unique($shizhu);

        sort($nianzhu);
        sort($yuezhu);
        sort($rizhu);
        sort($shizhu);
        return ['nianzhu'=>$nianzhu,'yuezhu'=>$yuezhu,'rizhu'=>$rizhu,'shizhu'=>$shizhu];
    }

    public function getZhenTaiYangShi()
    {

    }
}
