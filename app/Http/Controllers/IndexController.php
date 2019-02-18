<?php

namespace App\Http\Controllers;

use App\History;
use App\KXDictionary;
use App\Srcdata;
use App\Store;
use App\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\Gname;
use App\Models\Sentence;
use App\Models\Shici;
use App\Models\Recommendname;
use App\Services\NameTest;

class IndexController extends Controller
{

    public function nameTest(Request $request)
    {
        $xing = $request->get('xing');
        $ming = $request->get('ming');
        $shengri = $request->get('shengri');
        $nameTest = new NameTest($xing,$ming,$shengri);
        $result = $nameTest->analyze();
        return $this->success($result);
    }

    /**
     * 获取名字
     * @param $from
     * @param $type
     * @param int $size
     * @return mixed
     */
    public function getName(Request $request)
    {
        $page = $request->get('page');
        $xing = $request->get('xing');

        //先选取要使用的名句
        $sentences = Sentence::where('type',1)->forPage($page, 8)->get()->toArray();
        // dd($sentences);
        foreach($sentences as $sentence)
        {
            $sentences_name = Gname::where('from',$sentence['id'])->get()->toArray();

            $point = 0;
            foreach($sentences_name as $sname)
            {
                // 按结果算分
                $xing = $request->get('xing');
                $ming = $sname['name'];
                $shengri = date("Y-m-d H:i:s");
                $nameTest = new NameTest($xing,$ming,$shengri);
                $result = $nameTest->QuickDefen();
                // dd($result);
                if($result['defen'] > $point)
                {
                    $point = $result['defen'];
                    $high_score_name = $ming;
                    $zixing = $result['zixing'];
                    $id = $sname['id'];
                }
            }

            $resultdata['id']=$id;
            $resultdata['name']=$high_score_name;
            $resultdata['zixing']=$zixing;

            unset($high_score_name);
            unset($zixing);
            $resultdata['sentence'] = $sentence['verse'];
            $resultdata['author'] = $sentence['author'];
            $resultdata['title'] = $sentence['title'];
            $resultdata['core'] = $point;

            $returndata[] =  $resultdata;
        }

        if (empty($returndata)) {
            return ['success' => false, 'data' => null];
        } else {
            return $this->success($returndata);
        }
    }

    public function recommendName(Request $request)
    {
        $page = $request->get('page')?$request->get('page'):1;
        $xing = $request->get('xing');
        $type = $request->get('sex')?$request->get('sex'):1;
        $num = $request->get('num')?$request->get('num'):2;
        $re = Recommendname::where('type',$type)->where('num',$num)->limit(1000)->get()->toArray();
        foreach($re as $key => $nameinfo)
        {
            //拼名字
            $ming = $nameinfo['value1'].$nameinfo['value2'];
            $shengri = date("Y-m-d H:i:s");
            // var_dump($nameinfo['id']);
            $nameTest = new NameTest($xing,$ming,$shengri);
            $result = $nameTest->QuickDefen();
            //只保留得分95以上的名字
            if($result['defen'] < 85)
            {
                unset($re[$key]);
            }else{
                $re[$key]['score'] = $result['defen'];
                $re[$key]['zixing'] = $result['zixing'];
                $re[$key]['name'] = $ming;
            }
        }
        //根据高分排序
        // $scorearray = array_column($re,'score');
        // array_multisort($scorearray,SORT_DESC,$re);
        sort($re);
        // dd($re);
        return $this->success($re);
    }


    public function getNameInfo(Request $request)
    {
        $xing = $request->get('xing');
        $id = $request->get('id');
        $nameinfo = Gname::find($id)->toArray();
        $sentenceinfo = Sentence::find($nameinfo['from'])->toArray();

        $shicire = Shici::find($sentenceinfo['shici_id']);
        // dd($shiciInfo);

        $return['name'] =  $nameinfo['name'];

        // 按结果算分
        $ming = $return['name'];
        $shengri = date("Y-m-d H:i:s");
        $nameTest = new NameTest($xing,$ming,$shengri);
        $result = $nameTest->analyze();

        $return['score'] = $result['defen'];
        $return['zixing'] =  $result['zixing'];
        $return['sentence'] =  $sentenceinfo['verse'];
        $return['author'] = $sentenceinfo['author'];
        $return['title'] = $sentenceinfo['title'];
        if(!is_null($shicire))
        {
            $shiciInfo = $shicire->toArray();
            $return['dynasty'] = $shiciInfo['dynasty'];
            $return['content'] = $shiciInfo['content'];
            $return['translation'] = $shiciInfo['translation'];
            $return['appreciation'] = $shiciInfo['appreciation'];
        }

        return $this->success($return);
    }



}
