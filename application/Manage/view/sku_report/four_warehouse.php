
{include file="public/header" /}

<style>
    .pie-chart {margin-top: 32px}
</style>
<!-- 主体内容 -->
<script src="/static/echarts/dist/echarts.min.js"></script>
<script src="/static/echarts/test/lib/jquery.min.js"></script>
<div class="layui-body" id="LAY_app_body">
    <div class="right">
        <div class="title">SKU备货四仓率(当前只计算乐歌仓库)</div>
        <form class="layui-form" method="get">
            <div class="layui-input-inline w200">
                <input type="text" class="layui-input" id="sale_day" name="sale_day" value="{$sale_day}" placeholder="请选择日期">
            </div>
            <div class="layui-inline">
                <button class="layui-btn" lay-submit lay-filter="Search"><i class="layui-icon">&#xe615;</i> 查询</button>
            </div>
            <span class="total">三仓以上率：{:number_format(($storePercent[2]['count'] + $storePercent[3]['count']) / $storePercent[2]['countSum'], 4) * 100}%</span>
        </form>

        <table class="layui-table" lay-size="sm" style="width: 1600px">
            <colgroup>
                <col width="20%">
                <col width="20%">
                <col width="20%">
                <col width="20%">
                <col width="20%">
            </colgroup>
            <thead>
            <tr>
                <th class="tr">主件SKU个数</th>
                <th class="tr">单仓率</th>
                <th class="tr">两仓率</th>
                <th class="tr">三仓率</th>
                <th class="tr">四仓率</th>
            </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="tr">{$storePercent.0.countSum}</td>
                    <td class="tr">{:number_format($storePercent[0]['count'] / $storePercent[0]['countSum'], 4) * 100}%</td>
                    <td class="tr">{:number_format($storePercent[1]['count'] / $storePercent[1]['countSum'], 4) * 100}%</td>
                    <td class="tr">{:number_format($storePercent[2]['count'] / $storePercent[2]['countSum'], 4) * 100}%</td>
                    <td class="tr">{:number_format($storePercent[3]['count'] / $storePercent[3]['countSum'], 4) * 100}%</td>
                </tr>
            </tbody>
        </table>

        <table class="layui-table" lay-size="sm" style="width: 1600px">
            <colgroup>
                <col width="20%">
                <col width="20%">
                <col width="20%">
                <col width="20%">
                <col width="20%">
            </colgroup>
            <thead>
            <tr>
                <th class="tr">运营人员</th>
                <th class="tr">单仓率</th>
                <th class="tr">两仓率</th>
                <th class="tr">三仓率</th>
                <th class="tr">四仓率</th>
            </tr>
            </thead>
            <tbody>
            {foreach name="sellerData" key="key" item="v"}
            <tr>
                <td class="tr">{$key}</td>
                <td class="tr">{$v.1 * 100}%</td>
                <td class="tr">{$v.2 * 100}%</td>
                <td class="tr">{$v.3 * 100}%</td>
                <td class="tr">{$v.4 * 100}%</td>
            </tr>
            {/foreach}
            </tbody>
        </table>
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

    });
</script>

{include file="public/footer" /}
