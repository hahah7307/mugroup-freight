
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
</style>
<!-- 主体内容 -->
<div class="layui-body" id="LAY_app_body">
    <div class="right">
        <a href="{:session('back_url', '', 'manage')}" class="layui-btn layui-btn-danger layui-btn-sm fr"><i class="layui-icon">&#xe603;</i>返回上一页</a>
        <div class="title">{$date|strtotime|date="Y-m-d", ###}库龄 {$numStart}-{$num}天海外仓库存<strong>(不包含RETURN、ACCESSORY)</strong></div>
        <form class="layui-form" method="get">
            <div class="layui-input-inline w100">
                <input type="text" class="layui-input" name="seller" value="{$seller}" placeholder="主运营人员">
            </div>
            <div class="layui-inline">
                <button class="layui-btn" lay-submit lay-filter="Search"><i class="layui-icon">&#xe615;</i> 查询</button>
            </div>
        </form>

        <div class="layui-form table-flex">
            <table class="layui-table" lay-size="sm">
                <colgroup>
                    <col
                    <col>
                    <col>
                    <col>
                </colgroup>
                <thead>
                <tr>
                    <th colspan="4" class="tc">乐歌库存</th>
                </tr>
                </thead>
                <thead>
                <tr>
                    <th>仓库Sku</th>
                    <th>产品图片</th>
                    <th>主运营</th>
                    <th>库存数</th>
                </tr>
                </thead>
                <tbody>
                {foreach name="leList" item="v"}
                <tr class="sku-item" data-sku="{$v.lecangsCode}">
                    <td>{$v.lecangsCode}</td>
                    <td><img src="{$v.productImages}" height="80" alt=""></td>
                    <td class="tc">{$v.user_name}</td>
                    <td class="tr">{$v.goodsNum|number_format=###}</td>
                </tr>
                {/foreach}
                </tbody>
            </table>
            <table class="layui-table" lay-size="sm">
                <colgroup>
                    <col
                    <col>
                    <col>
                    <col>
                </colgroup>
                <thead>
                <tr>
                    <th colspan="4" class="tc">良仓库存</th>
                </tr>
                </thead>
                <thead>
                <tr>
                    <th>仓库Sku</th>
                    <th>产品图片</th>
                    <th>主运营</th>
                    <th>库存数</th>
                </tr>
                </thead>
                <tbody>
                {foreach name="lcList" item="item"}
                <tr class="sku-item" data-sku="{$item.product_sku}">
                    <td>{$item.product_sku}</td>
                    <td><img src="{$item.productImages}" height="80" alt=""></td>
                    <td class="tc">{$item.user_name}</td>
                    <td class="tr">{$item.sellable_quantity|number_format=###}</td>
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
            elem: '#sale_start',
            type: 'datetime'
        });
        laydate.render({
            elem: '#sale_end',
            type: 'datetime'
        });
        laydate.render({
            elem: '#qty_start',
            type: 'datetime'
        });
        laydate.render({
            elem: '#qty_end',
            type: 'datetime'
        });
    });
</script>

{include file="public/footer" /}
