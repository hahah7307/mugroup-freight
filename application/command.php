<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: yunwuxin <448901948@qq.com>
// +----------------------------------------------------------------------

return [
    'app\Manage\command\ProductUpdate',
    'app\Manage\command\OrderCapture',
    'app\Manage\command\OrderUpdate',
    'app\Manage\command\FinanceNotify',
    'app\Manage\command\PostalUpdate', // 自动更新易仓订单邮箱和地址附加费、旺季地址附加费、计费重
    'app\Manage\command\InventoryBatch', // 订单自动抓取易仓批次库存
    ];
