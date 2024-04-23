
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
        <div class="title">Sku日销量排行</div>
        <form class="layui-form" method="get">
            <div class="layui-inline w120">
                <select name="sale_order" lay-verify="">
                    <option value="DESC" {if condition="$sale_order eq 'DESC'"}selected{/if}>从高到低</option>
                    <option value="ASC" {if condition="$sale_order eq 'ASC'"}selected{/if}>从低到高</option>
                </select>
            </div>
            <div class="layui-input-inline w120">
                <input type="text" class="layui-input" id="sale_day" name="sale_day" value="{$sale_day}" placeholder="开始时间">
            </div>
            <span style="margin-left: 520px">销量区间：</span>
            <div class="layui-inline w120">
                <select name="qty_order" lay-verify="">
                    <option value="DESC" {if condition="$qty_order eq 'DESC'"}selected{/if}>从高到低</option>
                    <option value="ASC" {if condition="$qty_order eq 'ASC'"}selected{/if}>从低到高</option>
                </select>
            </div>
            <div class="layui-input-inline w100">
                <input type="text" class="layui-input" name="qty_start" value="{$qty_start}" placeholder="最少数量">
            </div>
            <div class="layui-input-inline w100">
                <input type="text" class="layui-input" name="qty_end" value="{$qty_end}" placeholder="最大数量">
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
                </colgroup>
                <thead>
                <tr>
                    <th>仓库Sku</th>
                    <th>产品图片</th>
                    <th>销量(个)</th>
                </tr>
                </thead>
                <tbody>
                {foreach name="saleList" item="v"}
                <tr class="sku-item" data-sku="{$v.warehouseSku}">
                    <td>{$v.warehouseSku}</td>
                    <td><img src="{$v.productImages}" height="50" alt=""></td>
                    <td class="tr">{$v.qty|number_format=###, 3}</td>
                </tr>
                {/foreach}
                </tbody>
            </table>
            <table class="layui-table" lay-size="sm">
                <colgroup>
                    <col
                    <col>
                    <col>
                </colgroup>
                <thead>
                <tr>
                    <th>仓库Sku</th>
                    <th>产品图片</th>
                    <th>销量(个)</th>
                </tr>
                </thead>
                <tbody>
                {foreach name="qtyList" item="item"}
                <tr class="sku-item" data-sku="{$item.warehouseSku}">
                    <td>{$item.warehouseSku}</td>
                    <td><img src="{$item.productImages}" height="50" alt=""></td>
                    <td class="tr">{$item.qty|number_format=###}</td>
                </tr>
                {/foreach}
                </tbody>
            </table>
            <table class="layui-table" lay-size="sm">
                <colgroup>
                    <col
                    <col>
                    <col>
                </colgroup>
                <thead>
                <tr>
                    <th>仓库Sku</th>
                    <th>海外仓库存(个)</th>
                    <th>销量(个)</th>
                </tr>
                </thead>
                <tbody>
                {foreach name="noList" item="vv"}
                <tr class="sku-vv" data-sku="{$vv.productSku}">
                    <td>{$vv.productSku}</td>
                    <td class="tr">{$vv.stock_qty|number_format=###}</td>
                    <td class="tr">{$vv.sale_qty|number_format=###}</td>
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
            elem: '#sale_day',
            type: 'date'
        });
        laydate.render({
            elem: '#qty_start',
            type: 'datetime'
        });
        laydate.render({
            elem: '#qty_end',
            type: 'datetime'
        });

        $(".sku-item").click(function(){
            let sku = $(this).data('sku');
            location.href = "/Manage/SkuReport/quantity/sku/" + sku + ".html";
        });
    });
</script>

{include file="public/footer" /}
