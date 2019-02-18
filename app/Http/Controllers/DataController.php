<?php

namespace App\Http\Controllers;

use App\Dicitionary;
use App\Models\Gname;
use App\Models\Sentence;
use Illuminate\Http\Request;
use Log;


class DataController extends Controller
{
    public function name()
    {
        //名句生成名字
        $datas = Sentence::where('id','>',4855)->where('id',"<=",5000)->get();

        foreach ($datas as $data) {
            var_dump($data->id);
            //断句
            $dealFirstData = $this->replaceSentenceData($data->verse);
            // dd($dealFirstData);
            //每一句单独拼名字
            $juziarr = explode("|", $dealFirstData);
            // dd($juziarr);
            foreach ($juziarr as $key => $juzi)
            {
                $dealData = $this->replaceAgainData($juzi);
                $var = explode("|", $dealData);
                // var_dump($var);
                $strarr=[];
                foreach ($var as $item) {
                    if (!empty($item)) {
                        //分字
                        $strarr[]=$tmpArr = $this->mbStrSplit($item);
                    }
                }
                if(!empty($strarr))
                {
                    //根据名句上下阙生成名字
                    $this->saveSentenceName($strarr, count($strarr), $data->id, $dealFirstData, $data->author);
                    unset($strarr);
                }
            }
            Log::info('save sentence gname, sentence_id : ' .$data->id.' sentence_title:'.$data->title);
            echo $data->title . '  -> ' . '处理完成';
            echo '<br>';
        }
    }

    public function saveSentenceName($names, $len, $from, $src, $author)
    {
        foreach($names[0] as $firstvalue)
        {
            //上阙结合下阙生成名字
                for($i=1;$i<$len;$i++)
                {
                    // dd($names[$i]);
                    foreach (($names[$i]) as $value) {
                        $create_name = $firstvalue . $value;
                        $Name = new Gname();
                        //检查名字是否已经存在
                        $name_exist_re = $Name->where('name',$create_name)->first();
                        if(is_null($name_exist_re))
                        {
                            $Name->name = $create_name;
                            $Name->from = $from;
                            $Name->type = 1;
                            $Name->num = 2;
                            $Name->description = $src;
                            // echo 'echo2Name --- '.json_encode($Name);
                            $Name->save();
                            // Log::info('save sentence gname, ganme : ' .$create_name.' save success');
                        }else{
                            // Log::info('save sentence gname, ganme : ' .$create_name.' has exist');
                        }
                    }
                }
        }

        $dealData = array_slice($names, 1);
        if(count($dealData) >=2)
        $this->saveSentenceName($dealData, count($dealData), $from, $src, $author);
    }


    public function replaceSentenceData($data)
    {
        $duanju = array(" ","　",". ", "?", "？ ", "。", "！ ", "！", "？", "；", "：");
        $fenju = array(
            "
　　", "

", "

", "
　　", "
　　", "\t", "\n", "\r", "《", "》", "”", "“", "、", "」", "「", "【", "】", "‘", "’", "◎");
        $yuqici = array("矣", "也", "乎", "哉", "而", "何", "之", "曰", "者", "耶", "邪", "呜", "呼", "哀", "咦", "嘘", "唏", "兮");
        $dealData = str_replace($duanju, '|', $data);
        $dealData = str_replace($fenju, '', $dealData);
        // $dealData = str_replace($yuqici, '', $dealData);

        return trim($dealData);
    }

    /**
     * 格式化数据,双词需要替换成|
     * @param $data
     * @param $type 1=单 2=双
     * @return string
     */
    public function replaceData($data)
    {
        $duanju = array(" ","　",". ", "?", "？ ", "。", "！ ", "！", "？", "；", "：");
        $fenju = array(
            "
　　", "

", "

", "
　　", "
　　", "\t", "\n", "\r", "《", "》", "”", "“", "、", "」", "「", "【", "】", "‘", "’", "◎");
        $yuqici = array("矣", "也", "乎", "哉", "而", "何", "之", "曰", "者", "耶", "邪", "呜", "呼", "哀", "咦", "嘘", "唏", "兮");
        $dealData = str_replace($duanju, '|', $data);
        $dealData = str_replace($fenju, '', $dealData);
        // $dealData = str_replace($yuqici, '', $dealData);

        return trim($dealData);
    }

    public function replaceAgainData($data)
    {
        $qian = array("　"," ", ", ","、", "，");

        $dealData = str_replace($qian, '|', $data);

        return trim($dealData);
    }


    /**
     * 汉字分词
     * @param $string
     * @param int $len
     * @return array
     */
    public function mbStrSplit($string, $len = 1)
    {
        $start = 0;
        $strlen = mb_strlen($string);
        while ($strlen) {
            $array[] = mb_substr($string, $start, $len, "utf8");
            $string = mb_substr($string, $len, $strlen, "utf8");
            $strlen = mb_strlen($string);
        }
        return $array;
    }

    /**
     * 输出双字
     * @param $names
     * @param $len
     * @param $from
     */
    public function echo2Name($names, $len, $from, $src, $author)
    {
        //$names = {"x","x","x"...}
        if ($len > 1) {
            for ($x = 1; $x < $len; $x++) {
                $create_name = $names[0] . $names[$x];
                $Name = new Gname();
                //检查名字是否已经存在
                $name_exist_re = $Name->where('name',$create_name)->first();
                if(is_null($name_exist_re))
                {
                    $Name->name = $create_name;
                    $Name->from = $from;
                    $Name->type = 1;
                    $Name->num = 2;
                    $Name->description = $src;
                    // echo 'echo2Name --- '.json_encode($Name);
                    $Name->save();
                }
            }
            $dealData = array_slice($names, 1);
            $this->echo2Name($dealData, count($dealData), $from, $src, $author);
        }
    }
}
