<?php

namespace App\Services;
use App\Models\KXZD;
use App\Models\SHULI;
use App\Models\Sancai;
use App\Models\XHZD;
use App\Models\Zonglun;
use Cache;
use Elasticsearch\ClientBuilder;
use Log;

class Nametest
{
    protected $xing;
    protected $ming;
    protected $xing_type;

    public function __construct($xing,$ming,$shengri)
    {
        $this->xing = $xing;
        $this->ming = $ming;
        $this->shengri = $shengri;
        if(mb_strlen($this->xing) == 1){
            $this->xing_type = 1;//单姓
        }else{
            $this->xing_type = 2;//复姓
        }


    }

    public function analyze()
    {
        // Cache::flush();die;
        $cachekey =$this->xing.$this->ming;
        $result = Cache::get($cachekey);
        if(!is_null($result)){
            //生肖喜忌
            $result = json_decode($result,true);
            $result['shengxiao'] = $this->shengxiao();
            return $result;
        }
        $name  = [];
        //五格
        $name['wuge'] = $this->getWuge();
        //五格吉凶
        $name['wuge_jixiong'] = $this->getWugeJixiong($name['wuge'])['wuge_jixiong'];
        $name['wuge_fenxi'] = $this->getWugeJixiong($name['wuge'])['wuge_fenxi'];
        //五行
        $name['wuge_wuxing'] = $this->getWuxingYY($name['wuge'])['wuxing'];
        //阴阳
        $name['yinyang'] = $this->getWuxingYY($name['wuge'])['yinyang'];
        //三才
        $name['sancai'] =  $name['wuge_wuxing']['tiange_wuxing'] . $name['wuge_wuxing']['renge_wuxing'] . $name['wuge_wuxing']['dige_wuxing'];
        $sancai = $this->sancai($name['sancai']);
        $name['sancai'] = [];
        $name['sancai']['jixiong'] = $sancai->ji_xiong;
        $name['sancai']['sancai_description'] = $sancai->description;
        $name['sancai']['sancai'] = $name['wuge_wuxing']['tiange_wuxing'] . $name['wuge_wuxing']['renge_wuxing'] . $name['wuge_wuxing']['dige_wuxing'];

        //生肖喜忌
        $name['shengxiao'] = $this->shengxiao();
        //得分
        $name['defen'] = $this->deFen($name['wuge_jixiong'],$sancai->ji_xiong);

        //三才五格
        //五格
        $return = [];
        $config = [
            'tiange'=>'天格',
            'renge'=>'人格',
            'waige'=>'外格',
            'dige'=>'地格',
            'zongge'=>'总格'
        ];
        foreach ($name['wuge'] as $key => $value) {
            $return['sancai_wuge']['wuge'][$key] = [
            'name'=>$config[$key],
            'bihua'=>$name['wuge'][$key],
            'jixiong'=>$name['wuge_jixiong'][$key.'_jixiong'],
            'wuxing'=>$name['wuge_wuxing'][$key.'_wuxing'],
            'yinyang'=>isset($name['yinyang'][$key.'_yinyang'])?$name['yinyang'][$key.'_yinyang']:'',
            'fenxi'=>$name['wuge_fenxi'][$key.'_fenxi']
            ];
        }

        //三才
        $return['sancai_wuge']['sancai'] = $name['sancai'];
        //生肖喜忌
        $return['shengxiao'] = $name['shengxiao'];
        //得分
        $return['defen'] = $name['defen'];

        //字形示意
        $return['zixing']= [];
        $str = $this->xing.$this->ming;
        $strlen = mb_strlen($str);

        $bihua = '';
        for ($i=0; $i < $strlen ; $i++) {
            $xhzd = XHZD::where('word',mb_substr($str,$i,1))->first();
            if(isset($xhzd)){
                $first_pinyin = explode(' ', $xhzd->pinyin)[0];
                $return['zixing'][] = ['char'=>$xhzd->word,'hzwx'=>$xhzd->hzwx,'jieshi'=>$xhzd->xhjieshi,'pinyin'=>$xhzd->pinyin,'first_pinyin'=>$first_pinyin];
                $tmp = isset($xhzd->kxbh)?$xhzd->kxbh.'  ':$xhzd->bihua.'  ';
                $bihua = $bihua.$tmp;
            }
        }

        //总论
        $return['zonglun'] = $this->getZonglun(rtrim($bihua));
        Cache::forever($cachekey,json_encode($return));
        return $return;
    }

    /**
     * 获取名字总论
     */
    public function getZonglun($bihua)
    {
        $cache_key = 'zonglun_'.$bihua;
        $cache_key= str_replace('  ','_', $cache_key);
        $content = Cache::get($cache_key);
        if(!is_null($content)){
            return json_decode($content,true);
        }
        $where = [
            ['bihua','=','13  12']
        ];
        $rs = Zonglun::where($where)->get();
        return $rs;
    }

    /**
     * 快速计算得分
     */
    public function QuickDefen()
    {
        $cachekey =$this->xing.$this->ming.'quick';
        $result = Cache::get($cachekey);
        if(!is_null($result)){
            return json_decode($result,true);
        }
        $name = [];
        $return = [];
        $name['wuge'] = $this->getWuge();
        $name['wuge_jixiong'] = $this->getWugeJixiong($name['wuge'])['wuge_jixiong'];
        $name['wuge_wuxing'] = $this->getWuxingYY($name['wuge'])['wuxing'];
        $name['sancai'] =  $name['wuge_wuxing']['tiange_wuxing'] . $name['wuge_wuxing']['renge_wuxing'] . $name['wuge_wuxing']['dige_wuxing'];
        $sancai = $this->sancai($name['sancai']);
        $return['defen'] = $this->deFen($name['wuge_jixiong'],$sancai->ji_xiong);
        //字形示意
        $return['zixing']= [];
        $str = $this->xing.$this->ming;
        $strlen = mb_strlen($str);
        for ($i=0; $i < $strlen ; $i++) {
            $xhzd = XHZD::where('word',mb_substr($str,$i,1))->first();
            $first_pinyin = explode(' ', $xhzd->pinyin)[0];
            $return['zixing'][] = ['char'=>$xhzd->word,'hzwx'=>$xhzd->hzwx,'pinyin'=>$xhzd->pinyin,'first_pinyin'=>$first_pinyin];
        }
        // //缓存服务变更为存储es
        // $client = ClientBuilder::create()->build();
        // $data[$cachekey] = $return;
        // $params = [
        //     'index' => 'namecreater',
        //     'type'  => 'names',
        //     // 'id'    => 'my_id',
        //     'body'  => $data
        // ];
        // $response = $client->index($params);
        // Log::info('cache result:'.$response);
        Cache::forever($cachekey,json_encode($return));
        return $return;
    }

    /**
     * 计算名字的得分
     * @return [type] [description]
     */
    public function deFen($wuge_jixiong,$sancai)
    {

        //三才  五格  权重各一半

        //五格权重
        //大吉大利 100 吉 75 半吉 62.5 半吉半凶 50 凶 25 最惨之数 0
        //天格：是先祖留传下来的，其数理对人影响不大。5%
        //地格：又称前运，影响人中年以前的活动力。20%
        //人格：又称主运，是整个姓名的中心点，影响人的一生命运。45%
        //总格：又称后运，影响人中年以后的命运。20%
        //外格：又称变格，影响人的社交能力、智慧等，其数理不用重点去看。10%
        $wugeconfig = [
            '大吉'=>100,
            '吉'=>90,
            '半吉'=>80,
            '半吉半凶'=>60,
            '凶'=>40,
            '大凶'=>30
        ];

        $sancaiconfig = [
            '大吉'=>100,
            '吉'=>95,
            '中吉'=>85,
            '吉多于凶'=>75,
            '吉凶参半'=>60,
            '凶多于吉'=>45,
            '大凶'=>30
        ];

        $wugescore = 0;
        foreach ($wuge_jixiong as $key => $value) {
            switch ($key) {
                case 'tiange_jixiong':
                    $tmpScore = $wugeconfig[$value] * 0.05;
                    break;
                case 'dige_jixiong':
                    $tmpScore = $wugeconfig[$value] * 0.2;
                    break;
                case 'renge_jixiong':
                    $tmpScore = $wugeconfig[$value] * 0.5;
                    break;
                case 'waige_jixiong':
                    $tmpScore = $wugeconfig[$value] * 0.05;
                    break;
                case 'zongge_jixiong':
                    $tmpScore = $wugeconfig[$value] * 0.2;
                    break;
                default:
                    $tmpScore = 0;
                    break;
            }
            $wugescore =  $wugescore +  $tmpScore;
        }

        //三才
        $sancaiScore = $sancaiconfig[$sancai];

        $score = $sancaiScore*0.25 + $wugescore*0.75;
        return round($score,1);
    }


    public function getWuge()
    {
        $wuge = [];
        $ming_first_bihua = $this->getBiHua(mb_substr($this->ming, 0,1)); //名字第一个字笔画
        $ming_last_bihua = $this->getBiHua(mb_substr($this->ming, -1,1)); //名字最后一个字笔画
        //总格
        $zongge = $this->getStrBihua($this->xing.$this->ming);//将姓与名相加即是总格数
        if($this->xing_type == 1){//单姓
            $xing_bihua = $this->getBiHua($this->xing);//姓的笔画
            //天格
            $tiange = $xing_bihua + 1; //姓氏笔划再加一数即是天格数
            //人格
            $renge = $xing_bihua + $ming_first_bihua; //将姓氏与第一个名字相加即是人格数
        }else{//复姓
            $xing_bihua = $this->getStrBihua($this->xing);//姓的笔画
            //天格
            $tiange = $xing_bihua;//将姓之笔划合计
             //人格
            $renge = $this->getBiHua(mb_substr($this->xing,1,1)) + $this->getBiHua(mb_substr($this->ming,0,1));//若复姓双名，则姓氏的第二个字笔画加名的第一个字的笔画； 复姓单名则姓氏的第二个字加名的笔画
        }

        //地格
        if(mb_strlen($this->ming) == 1){//地格  将第一个名字与第二个名字相加即是地格数（若是单名，将名字再加一数）
            $dige = $ming_first_bihua + 1;
        }else{
            $dige = $ming_first_bihua + $this->getBiHua(mb_substr($this->ming, 1,1));
        }
        //外格
        if(mb_strlen($this->ming) == 1){
            $waige = $zongge - $renge +2;//总格笔画数减去人格笔画数，如是单字名或单姓，再加一划
        }else{
            $waige = $zongge - $renge +1;
        }

        return [
            'tiange'=>$tiange,
            'renge'=>$renge,
            'dige'=>$dige,
            'waige'=>$waige,
            'zongge'=>$zongge
        ];

    }

    /**
     * 获取五格吉凶
     */
    protected function getWugeJixiong($wuge)
    {
        $wuge_jixiong = [];
        $wuge_fenxi = [];

        $wuge_jixiong['tiange_jixiong'] = $this->shuli($wuge['tiange'])->ji_xiong;
        $wuge_jixiong['renge_jixiong'] = $this->shuli($wuge['renge'])->ji_xiong;
        $wuge_jixiong['dige_jixiong'] = $this->shuli($wuge['dige'])->ji_xiong;
        $wuge_jixiong['waige_jixiong'] = $this->shuli($wuge['waige'])->ji_xiong;
        $wuge_jixiong['zongge_jixiong'] = $this->shuli($wuge['zongge'])->ji_xiong;

        $wuge_fenxi['tiange_fenxi'] = $this->shuli($wuge['tiange'])->all_description;
        $wuge_fenxi['renge_fenxi'] = $this->shuli($wuge['renge'])->all_description;
        $wuge_fenxi['dige_fenxi'] = $this->shuli($wuge['dige'])->all_description;
        $wuge_fenxi['waige_fenxi'] = $this->shuli($wuge['waige'])->all_description;
        $wuge_fenxi['zongge_fenxi'] = $this->shuli($wuge['zongge'])->all_description;

        $rs = ['wuge_jixiong'=>$wuge_jixiong,'wuge_fenxi'=>$wuge_fenxi];
        return $rs;
    }

    /**
     * 获取五格五行阴阳
     */
    protected function getWuxingYY($wuge)
    {
        $wuge_wuxing = [];
        $yinyang = [];

        $wuge_wuxing['tiange_wuxing'] = $this->wuxing($wuge['tiange'])['wuxing'];
        $wuge_wuxing['renge_wuxing'] = $this->wuxing($wuge['renge'])['wuxing'];
        $wuge_wuxing['dige_wuxing'] = $this->wuxing($wuge['dige'])['wuxing'];
        $wuge_wuxing['waige_wuxing'] = $this->wuxing($wuge['waige'])['wuxing'];
        $wuge_wuxing['zongge_wuxing'] = $this->wuxing($wuge['zongge'])['wuxing'];

        $yinyang['tiange_yinyang'] = $this->wuxing($wuge['tiange'])['yinyang'];
        $yinyang['renge_yinyang'] = $this->wuxing($wuge['renge'])['yinyang'];
        $yinyang['dige_yinyang'] = $this->wuxing($wuge['dige'])['yinyang'];
        $yinyang['waige_yinyang'] = $this->wuxing($wuge['waige'])['yinyang'];
        $yinyang['zongge_yinyang'] = $this->wuxing($wuge['zongge'])['yinyang'];

        return ['wuxing'=>$wuge_wuxing,'yinyang'=>$yinyang];

    }

    /**
     * @param $charModel
     * 获取笔画
     */
    private function getBiHua($zi)
    {
        $charModel = KXZD::where('word',$zi)->first();
        if(is_null($charModel)){
            $charModel = XHZD::where('word',$zi)->first();
            if(isset($charModel->id)){
                return $charModel->bihua;
            }else{
                return 6;
            }
        }


        //康熙笔画
        if(!empty($charModel->kxbh)){
            return $charModel->kxbh;
        }

        //繁体笔画
        if(!empty($charModel->fankxbihua)){
            return $charModel->fankxbihua;
        }

        //简体笔画
        if(!empty($charModel->jiankxbihua)){
            return $charModel->jiankxbihua;
        }

        return 0;
    }

    /**
     * 获取字符串的总笔画
     * @param  [type] $str [description]
     * @return [type]      [description]
     */
    public function getStrBihua($str)
    {
        $strlen = mb_strlen($str);
        $zongge = 0;
        for ($i=0; $i < $strlen ; $i++) {
            $bihua = $this->getBiHua(mb_substr($str,$i,1));
            $zongge = $zongge + $bihua;
        }

        return $zongge;
    }

    private function shengxiao()
    {
        $years = date('Y',strtotime($this->shengri));
        $shengxiao = $this->get_animals($years);

        $config = [
            'shu'=>'子鼠',
            'niu'=>'丑牛',
            'hu'=>'寅虎',
            'tu'=>'卯兔',
            'long'=>'辰龙',
            'she'=>'巳蛇',
            'ma'=>'午马',
            'yang'=>'未羊',
            'hou'=>'申猴',
            'ji'=>'酉鸡',
            'gou'=>'戌狗',
            'zhu'=>'亥猪',
        ];
        $xi = config("shengxiaoxiji.".$shengxiao.'.xi');
        $ji = config("shengxiaoxiji.".$shengxiao.'.ji');
        return ['xi'=>trim($xi),'ji'=>trim($ji),'shengxiao'=>$config[$shengxiao]];
    }

    private function get_animals($years)
    {
        //生肖喜忌
        $animals = ['shu','niu','hu','tu','long','she','ma','yang','hou','ji','gou','zhu'];
        $key = ($years -1900)%12;
        $shengxiao = $animals[$key];
        return $shengxiao;
    }

    private function wuxing($num)
    {
        $num = substr($num, -1);
        if($num == 0) {
            $num = 10;
        }
        $wuxing = null;
        if($num == 1 || $num == 2) {
            $wuxing = '木';
        }
        if($num == 3 || $num == 4) {
            $wuxing = '火';
        }
        if($num == 5 || $num == 6) {
            $wuxing = '土';
        }
        if($num == 7 || $num == 8) {
            $wuxing = '金';
        }
        if($num == 9 || $num == 10) {
            $wuxing = '水';
        }

        return [
            'wuxing'=>$wuxing,
            'yinyang'=>($num%2 == 0) ? '阴' : '阳'
        ];
    }
    private function shuli($num)
    {
        $shuli = SHULI::find($num);
        return $shuli;
    }
    private function sancai($name_wuxing)
    {
        $sancai = Sancai::where('sancai',$name_wuxing)->first();
        return $sancai;
    }
}
