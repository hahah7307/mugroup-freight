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
    'app\Manage\command\ProductUpdate', // * * * * *
    'app\Manage\command\OrderCalculate', // 自动计算易仓订单尾程 * * * * *
    'app\Manage\command\OrderCapture', // * * * * *
    'app\Manage\command\OrderUpdate', // * * * * *
    'app\Manage\command\FinanceNotify', // * * * * *
    'app\Manage\command\PostalUpdate', // 自动更新易仓订单邮箱和地址附加费、旺季地址附加费、计费重 * * * * *
    'app\Manage\command\InventoryBatch', // 自动抓取易仓批次库存 * * * * *
    'app\Manage\command\InventoryAdjustment', // 自动抓取批次库存调整记录 * * * * *
    'app\Manage\command\InventorySettlement', // 自动结算批次库龄对应费用 * * * * *
    'app\Manage\command\LcReceivingCapture', // 自动抓取最新良仓入库单记录 * * * * *
    'app\Manage\command\LcInventoryBatch', // 自动抓取良仓当日批次库存 * * * * *
    'app\Manage\command\ReceivingCapture', // 自动抓取最新易仓入库单记录 * * * * *
    'app\Manage\command\DateStockUpdate', // 自动抓取产品入库和消耗库存 * * * * *
    'app\Manage\command\DateStockCalculate', // 自动抓取产品入库和消耗库存 * * * * *
    'app\Manage\command\ShippingOrderCode', // 自动更新易仓订单海外仓单号 * * * * *
    'app\Manage\command\LcReceivingUpdate', //
    'app\Manage\command\ReceivingUpdate', //
    'app\Manage\command\LeInventoryBatch', //
    ];
