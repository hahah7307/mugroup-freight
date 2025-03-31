
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
        <div class="title">SKU发货四区率</div>
        <form class="layui-form" method="get">
            按包裹数排序：
            <div class="layui-inline w120">
                <select name="sale_order" lay-verify="">
                    <option value="DESC" {if condition="$sale_order eq 'DESC'"}selected{/if}>从多到少</option>
                    <option value="ASC" {if condition="$sale_order eq 'ASC'"}selected{/if}>从少到多</option>
                </select>
            </div>
            <div class="layui-input-inline w200">
                <input type="text" class="layui-input" id="sale_start" name="sale_start" value="{$sale_start}" placeholder="开始时间">
            </div>
            <div class="layui-input-inline w200">
                <input type="text" class="layui-input" id="sale_end" name="sale_end" value="{$sale_end}" placeholder="结束时间">
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
                    <th>缩略图</th>
                    <th>中文品名</th>
                    <th>四区包裹数</th>
                    <th>总包裹数</th>
                    <th>四区率</th>
                </tr>
                </thead>
                <tbody>
                {foreach name="saleList" key="key" item="v"}
                <tr class="sku-item" data-sku="{$v.warehouseSku}">
                    <td class="tr">{$key + 1}</td>
                    <td>{$v.warehouseSku}</td>
                    <td><img src="{$v.productImages}" height="80" alt=""></td>
                    <td>{$v.productTitle}</td>
                    <td class="tr">{$v.count}</td>
                    <td class="tr">{$v.countSum}</td>
                    <td class="tr">{$v.percent * 100}%</td>
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
            location.href = "/Manage/SkuReport/four_zone_detail/sku/" + sku + ".html";
        });
    });
</script>

{include file="public/footer" /}
