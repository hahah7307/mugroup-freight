
{include file="public/header" /}

<style>
    .layui-body {left: 220px!important;}
    .layui-form-label {width: 100px!important;}
    .layui-form-item .layui-inline {margin-right: 0!important;}
    .layui-form-label {width: 160px!important;}
    .w84 {width: 84px!important;}
    .deliver_num {width: 100px!important;}
    .table-flex {display: flex}
    /*.layui-table {display: flex}*/
    .select {margin-left: 0!important;}
    .warm-tips {display: inline-block; font-size: 14px; position: relative; top: 8px; left: 5px; color: #ce0000}
    .pie-chart {margin-top: 32px}
</style>
<!-- 主体内容 -->
<div class="layui-body" id="LAY_app_body">
    <div class="right">
        <div class="title">库存库龄月销量报表</div>
        <form class="layui-form" method="get">
            <div class="layui-input-inline w200">
                <input type="text" class="layui-input" id="date" name="date" value="{$date}" placeholder="开始时间">
            </div>
            <div class="layui-inline">
                <button class="layui-btn" lay-submit lay-filter="Search"><i class="layui-icon">&#xe615;</i> 查询</button>
            </div>
        </form>

        <div class="layui-form table-flex">
            <table class="layui-table" lay-size="sm">
                <colgroup>
                    <col>
                    <col>
                    <col>
                    <col>
                    <col>
                    <col>
                    <col>
                    <col>
                </colgroup>
                <thead>
                <tr>
                    <th colspan="8" class="tc">良仓</th>
                </tr>
                </thead>
                <thead>
                <tr>
                    <th>产品图片</th>
                    <th>仓库Sku</th>
                    <th>仓库代码</th>
                    <th>上月库存</th>
                    <th>当前库存</th>
                    <th>销售数量</th>
                    <th>当前库龄</th>
                    <th>主运营</th>
                </tr>
                </thead>
                <tbody>
                {foreach name="lc_list" key="key" item="v"}
                <tr {if condition="$v.sale_qty eq 0"}style="background: #f8b9b7"{/if}>
                    <td><img src="{$v.productImages}" height="80" alt=""></td>
                    <td>{$v.product_sku}</td>
                    <td>{$v.warehouse_code}</td>
                    <td class="tr">{$v.month_qty}</td>
                    <td class="tr">{$v.now_qty}</td>
                    <td class="tr">{$v.sale_qty}</td>
                    <td class="tr">{$v.stock_age}</td>
                    <td>{$v.user_name}</td>
                </tr>
                {/foreach}
                </tbody>
            </table>
            <table class="layui-table" lay-size="sm">
                <colgroup>
                    <col>
                    <col>
                    <col>
                    <col>
                    <col>
                    <col>
                    <col>
                    <col>
                </colgroup>
                <thead>
                <tr>
                    <th colspan="8" class="tc">乐歌</th>
                </tr>
                </thead>
                <thead>
                <tr>
                    <th>产品图片</th>
                    <th>仓库Sku</th>
                    <th>仓库代码</th>
                    <th>上月库存</th>
                    <th>当前库存</th>
                    <th>销售数量</th>
                    <th>当前库龄</th>
                    <th>主运营</th>
                </tr>
                </thead>
                <tbody>
                {foreach name="le_list" key="key" item="v"}
                <tr {if condition="$v.sale_qty eq 0"}style="background: #f8b9b7"{/if}>
                    <td><img src="{$v.productImages}" height="80" alt=""></td>
                    <td>{$v.lecangsCode}</td>
                    <td>{$v.warehouseCode}</td>
                    <td class="tr">{$v.month_qty}</td>
                    <td class="tr">{$v.now_qty}</td>
                    <td class="tr">{$v.sale_qty}</td>
                    <td class="tr">{$v.inventoryAge}</td>
                    <td>{$v.user_name}</td>
                </tr>
                {/foreach}
                </tbody>
            </table>
        </div>

    </div>
</div>
<script>
    layui.use(['form', 'jquery', 'laydate'], function(){
        var $ = layui.jquery,
            form = layui.form,
            laydate = layui.laydate;

        // 显示日期选择器
        laydate.render({
            elem: '#date',
            type: 'date'
        });

    });
</script>

{include file="public/footer" /}
