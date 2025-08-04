<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2018 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
use think\facade\Route;

Route::get('sitemap.xml', 'Sitemap/index');
Route::group('', function () {
    Route::get('', 'Index/index');
    Route::any('/detail/:id/:name?', 'Index/detail');
    Route::any('/cate/:id?/:name?', 'Index/cate');
    Route::any('/search/:keyword?', 'Index/search');
});

Route::get('commons/header', 'Index/menu');
// Route::get('Article/classification', 'Article/classification');
