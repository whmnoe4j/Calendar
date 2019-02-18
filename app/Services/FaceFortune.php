<?php

namespace App\Services;
use Cache;
use Log;

class FaceFortune
{
    protected $facedata;
    //参考 https://baike.baidu.com/item/%E9%9D%A2%E7%9B%B8
    public function __construct($facedata)
    {
        $this->facedata = $facedata;
    }

    /**
     * 五官分析
     * @return [type] [description]
     */
    public function wuguan()
    {
        $return = [];
        //耳朵
        $return['caiting_guan'] = '采听官:耳朵左右对称，光彩鲜明，代表身体健康，学习能力很强，功名会比较早发';

        // dd($this->facedata);
        //眉毛
        $mei_long = $this->caculateDistance($this->facedata['face_shape']['left_eyebrow'][0],$this->facedata['face_shape']['left_eyebrow'][4]); //眉长
        $yan_long = $this->caculateDistance($this->facedata['face_shape']['left_eye'][0],$this->facedata['face_shape']['left_eye'][4]);//眼长

        $meiyan_long = $this->caculateDistance($this->facedata['face_shape']['left_eyebrow'][3],$this->facedata['face_shape']['left_eye'][6]);//眉眼间距
        if($mei_long > $yan_long){
            $return['baoshou_guan'] = '保寿官:眉毛长约稍过眼长，眉最宽处约半个食指，眉眼间距约一指，两眉之间宽约一指半，眉要宽广清长，双分入鬓，或如悬新月，首尾丰盈，高居额中，乃为保寿官成。位置离眼睛不能太靠近，这样就是好的眉相，代表人际关系良好、个性温顺有礼、行为举止端正';
        }

        //眼睛
        $yan_height = $this->caculateDistance($this->facedata['face_shape']['left_eye'][6],$this->facedata['face_shape']['left_eye'][3]);//眼高
        if($yan_long > 2*$yan_height){
            $return['jiancha_guan'] ='监察官:眼要含藏不露，黑白分明，瞳子端正，光彩射人，或如凤目，细长藏秀，是为监察官成。心思必定纯正，而且具有才华智慧';
        }
        return $return;

    }

    /**
     * 三停分析
     * @return [type] [description]
     */
    public function santing()
    {
        return '上停长，少得志；中停长，做君子；下停长，老吉祥。 
        1.30岁以前运气：此时的观察重点，在耳朵和额头。有一种耳朵长得很高，耳的上方高过眼睛，有这种耳朵的人，学习和领悟能力很强，少年早发，许多童星均有提壶耳。有此耳朵的人，可望少年扬名，30岁前就有得名得利之机。此外，额头显示出的思考和智慧能力，额头好的人，能得到长上好的照应和遗传，青少年时期的运势是比较顺遂的，额头好的标准，是额头饱满、光滑，如果额头整体凹凸不平，左右两侧大小不均明显，虽然奋发努力，在此一时期却是劳多获少的  
        2.31-40岁运气：此时的观察重点，在眉毛和眼睛。眉毛淡的人比较寡情，虽然朋友也不少，但是交朋友重实际考量，常是相识满天下，知交无几人，走眉运时期（31-35岁）的运势，就像眉毛一般是平平淡淡的；若是眉形美好，浓淡有致即佳。若眼睛神光充足，眼神安定又炯炯有神的人（36-40岁运势），不论正路或异路均财荣；不过眼睛有神却带邪的人，好的大运过後，仍会有相当后遗症的 此时的观察重点，在鼻子和两颧。鼻子和两颧搭配合宜，即鼻子相理佳，又有两颧护卫，鼻颧看起来很协调相配，没有任何一方喧宾夺主，则在40-50岁时期，中年事业顺利，自助亦得人助。鼻子好，但是两颧不好的人，中年难发，而且很不适合与人合夥，容易有是非与金钱损失，工作上最好是单打独斗，并培养独特的技能；鼻子不好，但是两颧好的人，则适合替人工作，尚能得同事朋友的助力求得温饱，不过意志力和行动力不足，故不宜创业。鼻、颧都不好的人，此时期保守为宜，一动不如一静
        3.41-50岁运气：此时的观察重点，在鼻子和两颧。鼻子和两颧搭配合宜，即鼻子相理佳，又有两颧护卫，鼻颧看起来很协调相配，没有任何一方喧宾夺主，则在40-50岁时期，中年事业顺利，自助亦得人助。鼻子好，但是两颧不好的人，中年难发，而且很不适合与人合夥，容易有是非与金钱损失，工作上最好是单打独斗，并培养独特的技能；鼻子不好，但是两颧好的人，则适合替人工作，尚能得同事朋友的助力求得温饱，不过意志力和行动力不足，故不宜创业。鼻、颧都不好的人，此时期保守为宜，一动不如一静
        4.51岁以后运气：此时的观察重点，在嘴唇和下巴。口形良好厚实，下巴宽厚有力，且人中「上窄下宽」很清楚的人，在51岁上了年纪后，生活上是平安康泰，有安享晚年的福气。至于嘴唇歪斜不正，嘴角向下郁郁不乐，同时下巴尖削的人，晚年运势堪虑，许多落魄的流浪汉皆是此种相貌。';
    }

    /**
     * 十二宫分析
     * @return [type] [description]
     */
    public function shiergong()
    {
        return [];
    }

    /**
     * 计算两点间的距离
     */
    public function caculateDistance($pointA,$pointB)
    {
        $dtx = $pointA['x'] - $pointB['x'];
        $dty = $pointA['y'] - $pointB['y'];
        $dtx_pow = pow($dtx,2);
        $dty_pow = pow($dty,2);

        $plus = $dtx_pow + $dty_pow;
        $ditance = abs(sqrt($plus));
        return $ditance;
    }
}

