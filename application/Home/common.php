<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 流年 <liu21st@gmail.com>
// +----------------------------------------------------------------------

// 异常错误报错级别,
error_reporting(E_ERROR | E_PARSE );

// 应用公共文件

// 获取前台跳转目录
function jump($url, $params = array())
{
    if (!empty($params)) {
        if (empty($params['slug'])) {
            return false;
        }
    }
    $urllist = explode('/', $url);
    $controller = count($urllist) == 1 ? request()->controller() : $urllist[0];
    $action = count($urllist) == 1 ? $urllist[0] : $urllist[1];
    $route = $controller . '/' . $action;
    $seo_url = route_decode($route);
    $seo_list = explode('.', $seo_url);
    if (count($seo_list) > 1 && $seo_list[1] == 'html') {
        $res_url = $seo_url;
    } else {
        if (empty($params['slug'])) {
            return false;
        }
        $res_url = $seo_url . '/' . $params['slug'] . '.html';
    }
    return $res_url;
}

function route_decode($route)
{
    $route_list = [
        'Index/index'           =>  '/',
        'Article/index'         =>  '/newsCategory',
        'Article/detail'        =>  '/news',
        'Page/detail'           =>  '/page',
        'Member/page'           =>  '/apply.html',
        'Member/feature'        =>  '/feature.html',
        'Member/detail'         =>  '/member',
        'Notice/index'          =>  '/bulletin.html',
        'Notice/detail'         =>  '/notice'
    ];
    foreach ($route_list as $key => $value) {
        if ($key == $route) {
            return $value;
        }
    }
}

