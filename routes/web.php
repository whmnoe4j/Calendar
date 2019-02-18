<?php
//获取名字列表
Route::get('/getName', 'IndexController@getName');

Route::get('/getNameInfo', 'IndexController@getNameInfo');

Route::get('/namechild/testname', 'IndexController@nameTest');

Route::get('/recommendName', 'IndexController@recommendName');


Route::get('/name', 'DataController@name');
