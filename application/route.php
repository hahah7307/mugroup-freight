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

// return [
//     '__pattern__' => [
//         'name' => '\w+',
//     ],
//     '[hello]'     => [
//         ':id'   => ['index/hello', ['method' => 'get'], ['id' => '\d+']],
//         ':name' => ['index/hello', ['method' => 'post']],
//     ],

// ];’

use think\Route;

Route::get('newsCategory/:slug','Home/Article/index');

Route::get('news/:slug','Home/Article/detail');

Route::get('bulletin','Home/Notice/index');

Route::get('notice/:slug','Home/Notice/detail');

Route::get('download/:slug','Home/Download/index');

Route::get('apply','Home/Member/page');

Route::get('feature','Home/Member/feature');

Route::get('member/:slug','Home/Member/detail');

Route::get('page/:slug','Home/Page/detail');

