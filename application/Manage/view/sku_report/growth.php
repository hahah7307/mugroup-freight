
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
        <div class="title">销量月增长报表<strong>(不包含RETURN、ACCESSORY)</strong><span class="red">(销量有增长但增长率为0%表示上月无销量，销量增长为负且增长率为-100%表示本月无销量，上上月和上周同理)</span></div>
        <form class="layui-form" method="get">
            <div class="layui-input-inline w200">
                <input type="text" class="layui-input" id="start" name="start" value="{$start}" placeholder="开始时间">
            </div>
            <div class="layui-input-inline w200">
                <input type="text" class="layui-input" id="end" name="end" value="{$end}" placeholder="结束时间">
            </div>
            环比上月排序：
            <div class="layui-inline w120">
                <select name="last_order" lay-verify="">
                    <option value="DESC" {if condition="$last_order eq 'DESC'"}selected{/if}>从高到低</option>
                    <option value="ASC" {if condition="$last_order eq 'ASC'"}selected{/if}>从低到高</option>
                </select>
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
                    <col>
                    <col>
                    <col>
                </colgroup>
                <thead>
                <tr>
                    <th>名次</th>
                    <th>仓库Sku</th>
                    <th>产品图片</th>
                    <th>本月销量</th>
                    <th>上月销量</th>
                    <th>环比上月增长销量</th>
                    <th>环比上月增长率</th>
                </tr>
                </thead>
                <tbody>
                {foreach name="saleList" key="key" item="v"}
                <tr class="sku-item" data-sku="{$v.warehouseSku}">
                    <td class="tr">{$key + 1}</td>
                    <td>{$v.warehouseSku}</td>
                    <td><img src="{$v.productImages}" height="80" alt=""></td>
                    <td class="tr">{$v.current|number_format=###}</td>
                    <td class="tr">{$v.last|number_format=###}</td>
                    <td class="tr">{$v.diff|number_format=###}</td>
                    <td class="tr">{$v.diff_rate * 100}%</td>
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
            elem: '#start',
            type: 'datetime'
        });
        laydate.render({
            elem: '#end',
            type: 'datetime'
        });

        $(".sku-item").click(function(){
            let sku = $(this).data('sku');
            location.href = "/Manage/SkuReport/sku_growth/sku/" + sku + ".html";
        });
    });
</script>

{include file="public/footer" /}
