
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
        <div class="title">SKU月仓储费总排行榜</div>
        <form class="layui-form" method="get">
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
                <select name="percent_order" lay-verify="">
                    <option value="DESC" {if condition="$percent_order eq 'DESC'"}selected{/if}>从高到低</option>
                    <option value="ASC" {if condition="$percent_order eq 'ASC'"}selected{/if}>从低到高</option>
                </select>
            </div>
            <div class="layui-input-inline w200">
                <input type="text" class="layui-input" id="percent_start" name="percent_start" value="{$percent_start}" placeholder="开始时间">
            </div>
            <div class="layui-input-inline w200">
                <input type="text" class="layui-input" id="percent_end" name="percent_end" value="{$percent_end}" placeholder="结束时间">
            </div>
            <div class="layui-inline">
                <button class="layui-btn" lay-submit lay-filter="Search"><i class="layui-icon">&#xe615;</i> 查询</button>
            </div>
        </form>

        <div class="layui-form table-flex">
            <table class="layui-table" lay-size="sm">
                <colgroup>
                    <col width="50">
                    <col>
                    <col>
                    <col>
                </colgroup>
                <thead>
                <tr>
                    <th>名次</th>
                    <th>仓库Sku</th>
                    <th>产品图片</th>
                    <th>仓储费(美金)</th>
                </tr>
                </thead>
                <tbody>
                {foreach name="saleList" key="key" item="v"}
                <tr>
                    <td class="tr">{$key + 1}</td>
                    <td>{$v.main_sku}</td>
                    <td><img src="{$v.productImages}" height="80" alt=""></td>
                    <td class="tr">{$v.total|number_format=###, 3}</td>
                </tr>
                {/foreach}
                </tbody>
            </table>
            <table class="layui-table" lay-size="sm">
                <colgroup>
                    <col width="50">
                    <col
                    <col>
                    <col>
                </colgroup>
                <thead>
                <tr>
                    <th>名次</th>
                    <th>仓库Sku</th>
                    <th>产品图片</th>
                    <th>仓储费占销售额(%)</th>
                </tr>
                </thead>
                <tbody>
                {foreach name="percentList" key="k" item="item"}
                <tr class="sku-item">
                    <td class="tr">{$k + 1}</td>
                    <td>{$item.warehouse_sku}</td>
                    <td><img src="{$item.productImages}" height="80" alt=""></td>
                    <td class="tr">{$item.percent|number_format=###, 2}</td>
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
            type: 'month'
        });
        laydate.render({
            elem: '#sale_end',
            type: 'month'
        });
        laydate.render({
            elem: '#percent_start',
            type: 'month'
        });
        laydate.render({
            elem: '#percent_end',
            type: 'month'
        });
    });
</script>

{include file="public/footer" /}
