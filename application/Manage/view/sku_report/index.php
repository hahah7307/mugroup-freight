
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
        <div class="title">Sku销售销量排行</div>
        <form class="layui-form" method="get">
            销售：
            <div class="layui-inline w120">
                <select name="sale_order" lay-verify="">
                    <option value="DESC" {if condition="$sale_order eq 'DESC'"}selected{/if}>从高到低</option>
                    <option value="ASC" {if condition="$sale_order eq 'ASC'"}selected{/if}>从低到高</option>
                </select>
            </div>
            <div class="layui-input-inline w200">
                <input type="text" class="layui-input" id="sale_start" name="sale_start" value="{$sale_start}" placeholder="开始时间">
            </div>
            <div class="layui-input-inline w200">
                <input type="text" class="layui-input" id="sale_end" name="sale_end" value="{$sale_end}" placeholder="结束时间">
            </div>
            <span style="margin-left: 120px">销量：</span>
            <div class="layui-inline w120">
                <select name="qty_order" lay-verify="">
                    <option value="DESC" {if condition="$qty_order eq 'DESC'"}selected{/if}>从高到低</option>
                    <option value="ASC" {if condition="$qty_order eq 'ASC'"}selected{/if}>从低到高</option>
                </select>
            </div>
            <div class="layui-input-inline w200">
                <input type="text" class="layui-input" id="qty_start" name="qty_start" value="{$qty_start}" placeholder="开始时间">
            </div>
            <div class="layui-input-inline w200">
                <input type="text" class="layui-input" id="qty_end" name="qty_end" value="{$qty_end}" placeholder="结束时间">
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
                    <th>销售(美金)</th>
                </tr>
                </thead>
                <tbody>
                {foreach name="saleList" item="v"}
                <tr class="sku-item" data-sku="{$v.warehouseSku}">
                    <td>{$v.warehouseSku}</td>
                    <td><img src="{$v.productImages}" height="50" alt=""></td>
                    <td>{$v.sale|number_format=###, 3}</td>
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
                    <td>{$item.qty|number_format=###}</td>
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

        $(".sku-item").click(function(){
            let sku = $(this).data('sku');
            location.href = "/Manage/SkuReport/quantity/sku/" + sku + ".html";
        });
    });
</script>

{include file="public/footer" /}
