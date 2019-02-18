<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Rhesi;
use App\Models\Explain;
use App\Services\Calendar;
use App\Models\LuckyDate;
use DB;
use GuzzleHttp\Client;
use App\Services\FortuneTelling;
use App\Services\TencentAiPlatform;
use App\Services\FaceFortune;
class CalendarController extends Controller
{

    public function __construct()
    {
        $this->guzzleclient = new Client();
    }

    public function getWeather(Request $request)
    {
        $LngLat = $request->get('LngLat');
        // 获取经纬度所在城市
        $city = $this->getCity($LngLat);
        //获取城市当日天气并缓存
        $re = $this->getUrlWeather($city);

        return $this->success($re);
    }

    public function getTips(Request $request)
    {
        $openid = is_null($request->get('openid'))?'oOw4VwOigz2TzqEVp7LFoHFpf':$request->get('openid');
        $type = is_null($request->get('type'))?null:$request->get('type');
        $date = is_null($request->get('date'))?date("Y-m-d"):$request->get('date');
        $calendar = new Calendar($openid,$date,$type);
        $result = $calendar->getTips();
        return $this->success($result);
    }

    private function getCity($LngLat)
    {
        $response = $this->guzzleclient->request('GET', 'https://restapi.amap.com/v3/geocode/regeo', [
            'query' => [
                'key'      => config('app.amap_key'),
                'location'     => $LngLat
            ]
        ]);

        $result = $response->getBody()->getContents();
        $cityarr = json_decode($result,true);
        // dd(config('app.amap_key'));
        if(($cityarr['regeocode']['addressComponent']['city']) != [])
        {
            $city = $cityarr['regeocode']['addressComponent']['city'];
        }else{
            $city = $cityarr['regeocode']['addressComponent']['province'];
        }
        return $city;
    }


    private function getUrlWeather($city)
    {
        $response = $this->guzzleclient->request('GET', 'https://free-api.heweather.com/s6/weather/forecast', [
            'query' => [
                'key'      => config('app.heweather_key'),
                'location'     => $city
            ]
        ]);

        $result = $response->getBody()->getContents();
        return $result;
    }

    public function getLuckydate(Request $request)
    {
        $names = json_decode($request->get('names'),true);
        $start_date = $request->get('start_date');
        $end_date = $request->get('end_date');
        $luckyDates = LuckyDate::select(DB::raw('count(*) as count'),'date')->having('count','=',count($names))->whereBetween('date',[$start_date,$end_date])->whereIn('name',$names)->groupBy('date')->get();
        return $this->success($luckyDates);
    }

    public function greetingCardWishes(Request $request)
    {
        $tag_id = 31;
        // $start_wishes_id = 4115;
        $ids = [25869,25873,28632,30823,30834,30966,30983,30991,34107,34108];
        $christmas = DB::table('wishes')->where('tag_id',$tag_id)->whereIn('id',$ids)->limit(10)->get();
        return $this->success($christmas);
    }

    /**
     * 黄历名词解释
     */
    public function getExplanation(Request $request)
    {
        //nouns
        $nonus = json_decode($request->get('nonus'),true);
        foreach ($nonus as $key => $value) {
            //获取名词解释
            $explanation = Explain::where('noun',$value)->first();
            $nonus[$key] = ['noun'=>$value,'explain'=>isset($explanation->explain)?$explanation->explain:''];
        }
        return $this->success($nonus);
    }

    /**
     * 获取八字排盘
     */
    public function getBazi(Request $request)
    {
        $sex = $request->get('sex');//1男 2女
        $birthdate = $request->get('birthdate');
        $birthtime = $request->get('birthtime');
        $FortuneRelling = new FortuneTelling;
        $rs = $FortuneRelling->baiZiPaiPan($sex,$birthdate,$birthtime);
        return $this->success($rs);
    }

    /**
     * 腾讯ai人脸分析
     */
    public function face_detectface(Request $request)
    {
        $imageurl = $request->get('image_url');
        $app_id = env('TENCENT_AI_APP_ID');
        $app_key = env('TENCENT_AI_APP_KEY');
        TencentAiPlatform::setAppInfo($app_id,$app_key);
        $data   = file_get_contents($imageurl);
        $base64 = base64_encode($data);

        // 设置请求数据
        $params = array(
            'image'      => $base64,
            'mode'       => '0',
            'time_stamp' => strval(time()),
            'nonce_str'  => strval(rand()),
            'sign'       => '',
        );
        $rs = TencentAiPlatform::face_detectface($params);
        $rs = json_decode($rs,true);
        if($rs['ret'] == 0){
            return $this->success($rs);
        }else{
            return $this->fail();
        }
    }

    /**
     * 人脸算命结果
     */
    public function face_detectface_result(Request $request)
    {
        $imageurl = $request->get('image_url');
        $app_id = env('TENCENT_AI_APP_ID');
        $app_key = env('TENCENT_AI_APP_KEY');
        TencentAiPlatform::setAppInfo($app_id,$app_key);
        $data   = file_get_contents($imageurl);
        $base64 = base64_encode($data);

        // 设置请求数据
        $params = array(
            'image'      => $base64,
            'mode'       => '0',
            'time_stamp' => strval(time()),
            'nonce_str'  => strval(rand()),
            'sign'       => '',
        );
        $rs = TencentAiPlatform::face_detectface($params);
        $rs = json_decode($rs,true);
        if($rs['ret'] == 0){
            $facefortune = new FaceFortune($rs['data']['face_list'][0]);
            $wuguan_fenxi = $facefortune->wuguan($rs['data']);
            $santing_fenxi = $facefortune->santing($rs['data']);
            $shiergong_fenxi = $facefortune->shiergong($rs['data']);

            $return['wuguan'] =  $wuguan_fenxi;
            $return['santing'] =  $santing_fenxi;
            $return['shiergong'] =  $shiergong_fenxi;
            return $this->success($return);
        }else{
            return $this->fail();
        }
    }
}
