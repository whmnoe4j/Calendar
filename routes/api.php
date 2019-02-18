<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:api');

Route::get('weather', 'Api\CalendarController@getWeather');
Route::get('tips', 'Api\CalendarController@getTips');
//黄历择吉日
Route::get('luckydate', 'Api\CalendarController@getLuckydate');
//贺卡祝福语
Route::get('greetingcardwishes', 'Api\CalendarController@greetingCardWishes');
//黄历名词解释
Route::get('explanation','Api\CalendarController@getExplanation');
//八字排盘
Route::get('getbazi','Api\CalendarController@getBazi');

//腾讯ai人工智能
Route::group(['prefix'=>'tencentai','namespace'=>'Api'],function(){
	Route::get('face/face_detectface','CalendarController@face_detectface');//人脸分析
	Route::get('face/face_detectface/result','CalendarController@face_detectface_result');//人脸分析结果
});
