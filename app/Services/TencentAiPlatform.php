<?php
namespace App\Services;

class TencentAiPlatform
{
	const CHUNK_SIZE = 6400;
	const API_URL_PATH = 'https://api.ai.qq.com/fcgi-bin/';

	private static $_app_id;
	private static $_app_key;
	private static $_err_msg;

    private static $_http_code;

    // _is_base64 ：判断一个字符串是否经过base64
    // 参数说明
    //   - $str：待判断的字符串
    // 返回数据
    //   - 该字符串是否经过base64（true/false）
	private static function _is_base64($str)
	{
	    return $str == base64_encode(base64_decode($str)) ? true : false;
	}

    // texttrans ：调用文本翻译（AI Lab）接口
    // 参数说明
    //   - $params：type-翻译类型；text-待翻译文本。（详见http://ai.qq.com/doc/nlptrans.shtml）
    // 返回数据
    //   - $response: ret-返回码；msg-返回信息；data-返回数据（调用成功时返回）；http_code-Http状态码（Http请求失败时返回）
	public static function texttrans($params)
	{
		$params['sign'] = self::getReqSign($params);
		$url = self::API_URL_PATH . '/nlp/nlp_texttrans';
		$response = self::doHttpPost($url, $params);
		return $response;
	}

    // generalocr ：调用通用OCR识别接口
    // 参数说明
    //   - $params：image-待识别图片。（详见http://ai.qq.com/doc/ocrgeneralocr.shtml）
    // 返回数据
    //   - $response: ret-返回码；msg-返回信息；data-返回数据（调用成功时返回）；http_code-Http状态码（Http请求失败时返回）
	public static function generalocr($params)
	{
		if (!self::_is_base64($params['image']))
		{
		    $params['image'] = base64_encode($params['image']);
		}
		$params['sign'] = self::getReqSign($params);
		$url = self::API_URL_PATH . '/ocr/ocr_generalocr';
		$response = self::doHttpPost($url, $params);
		return $response;
	}

    // wxasrs ：调用语音识别-流式版(WeChat AI)接口
    // 参数说明
    //   - $params：speech-待识别的整段语音，不需分片；
    //              format-语音格式；
    //              rate-音频采样率编码；
    //              bits-音频采样位数；
    //              speech_id-语音ID。（详见http://ai.qq.com/doc/aaiasr.shtml）
    // 返回数据
    //   - $response: ret-返回码；msg-返回信息；data-返回数据（调用成功时返回）；http_code-Http状态码（Http请求失败时返回）
	public static function wxasrs($params)
	{
		$speech = self::_is_base64($params['speech']) ? base64_decode($params['speech']) : $params['speech'];
		unset($params['speech']);
		$speech_len = strlen($speech);
		$total_chunk = ceil($speech_len / self::CHUNK_SIZE);
        $params['cont_res'] = 0;
		for($i = 0; $i < $total_chunk; ++$i)
		{
			$chunk_data = substr($speech, $i * self::CHUNK_SIZE, self::CHUNK_SIZE);
			$params['speech_chunk'] = base64_encode($chunk_data);
			$params['len']          = strlen($chunk_data);
		    $params['seq']          = $i * self::CHUNK_SIZE;
		    $params['end']          = ($i == ($total_chunk-1)) ? 1 : 0;
			$response = self::wxasrs_perchunk($params);
		}
		return $response;
	}

    // wxasrs_perchunk ：调用语音识别-流式版(WeChat AI)接口
    // 参数说明
    //   - $params：speech_chunk-待识别的语音分片；
    //              seq-语音分片所在语音流的偏移量；
    //              len-分片长度；
    //              end-是否结束分片；
    //              cont_res-是否获取中间识别结果；
    //              format-语音格式；
    //              rate-音频采样率编码；
    //              bits-音频采样位数；
    //              speech_id-语音ID。（详见http://ai.qq.com/doc/aaiasr.shtml）
    // 返回数据
    //   - $response: ret-返回码；msg-返回信息；data-返回数据（调用成功时返回）；http_code-Http状态码（Http请求失败时返回）
	public static function wxasrs_perchunk($params)
	{
		if (!self::_is_base64($params['speech_chunk']))
		{
		    $params['speech_chunk'] = base64_encode($params['speech_chunk']);
		}
		$params['sign'] = self::getReqSign($params);
		$url = self::API_URL_PATH . '/aai/aai_wxasrs';
		$response = self::doHttpPost($url, $params);
		return $response;
	}

	// generalocr ：调用通用OCR识别接口
    // 参数说明
    //   - $params：image-待识别图片。（详见http://ai.qq.com/doc/ocrgeneralocr.shtml）
    // 返回数据
    //   - $response: ret-返回码；msg-返回信息；data-返回数据（调用成功时返回）；http_code-Http状态码（Http请求失败时返回）
	public static function face_detectface($params)
	{
		$params['sign'] = self::getReqSign($params);
		$url = self::API_URL_PATH . '/face/face_detectface';
		$response = self::doHttpPost($url, $params);
		return $response;
	}



	//配置


    // setAppInfo ：设置AppID和AppKey
    // 参数说明
    //   - $app_id
    //   - $app_key
    // 返回数据
    //   - 是否设置成功
	public static function setAppInfo($app_id, $app_key)
	{
		if (!is_numeric($app_id) || $app_id <= 0)
		{
			self::$_err_msg = "invalid app_id:[{$app_id}]";
			return false;
		}
		self::$_app_id  = $app_id;
		self::$_app_key = $app_key;
		return true;
	}

	public static function getErrMsg()
	{
		return self::$_err_msg;
	}

	public static function getAppId()
	{
		return self::$_app_id;
	}

		public static function getAppKey()
	{
		return self::$_app_key;
	}



	//签名
	// getReqSign ：根据 接口请求参数 和 应用密钥 计算 请求签名
    // 参数说明
    //   - $params：接口请求参数（特别注意：不同的接口，参数对一般不一样，请以具体接口要求为准）
    // 返回数据
    //   - 签名结果
    public static function getReqSign(&$params)
    {
        // 0. 补全基本参数
        $params['app_id'] = self::getAppId();

        if (!$params['nonce_str'])
        {
            $params['nonce_str'] = uniqid("{$params['app_id']}_");
        }

        if (!$params['time_stamp'])
        {
            $params['time_stamp'] = time();
        }

        // 1. 字典升序排序
        ksort($params);

        // 2. 拼按URL键值对
        $str = '';
        foreach ($params as $key => $value)
        {
            if ($value !== '')
            {
                $str .= $key . '=' . urlencode($value) . '&';
            }
        }

        // 3. 拼接app_key
        $str .= 'app_key=' . self::getAppKey();

        // 4. MD5运算+转换大写，得到请求签名
        $sign = strtoupper(md5($str));
        return $sign;
    }


    //请求

    // getHttpCode ：获取上一次Http请求的状态码
    // 返回数据
    //   - 上一次Http请求的状态码
    public static function getHttpCode()
    {
        return self::_http_code;
    }

    // doHttpPost ：执行POST请求，并取回响应结果
    // 参数说明
    //   - $url   ：接口请求地址
    //   - $params：完整接口请求参数（特别注意：不同的接口，参数对一般不一样，请以具体接口要求为准）
    // 返回数据
    //   - 返回false表示失败，否则表示API成功返回的HTTP BODY部分
    public static function doHttpPost($url, $params)
    {
        $curl = curl_init();

        $response = false;
        do
        {
            // 1. 设置HTTP URL (API地址)
            curl_setopt($curl, CURLOPT_URL, $url);

            // 2. 设置HTTP HEADER (表单POST)
            $head = array(
                'Content-Type: application/x-www-form-urlencoded'
            );
            curl_setopt($curl, CURLOPT_HTTPHEADER, $head);

            // 3. 设置HTTP BODY (URL键值对)
            $body = http_build_query($params);
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $body);

            // 4. 调用API，获取响应结果
            curl_setopt($curl, CURLOPT_HEADER, false);
            curl_setopt($curl, CURLOPT_NOBODY, false);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            // curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, true);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            $response = curl_exec($curl);
            self::$_http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            if (self::$_http_code != 200)
            {
                $msg = curl_error($curl);
                $response = json_encode(array('ret' => -1, 'msg' => "sdk http post err: {$msg}", 'http_code' => self::$_http_code));
                break;
            }
        } while (0);

        curl_close($curl);
        return $response;
    }
}