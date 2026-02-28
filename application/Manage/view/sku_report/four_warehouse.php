
{include file="public/header" /}

<style>
    .pie-chart {margin-top: 32px}
    .product-img {position: absolute; right: 250px; top: 250px}
</style>
<!-- 主体内容 -->
<script src="/static/echarts/dist/echarts.min.js"></script>
<script src="/static/echarts/test/lib/jquery.min.js"></script>
<div class="layui-body" id="LAY_app_body">
    <div class="right">
        <div class="title">SKU备货四仓率(含乐歌、无忧达海外仓)</div>
        <form class="layui-form" method="get">
            <div class="layui-input-inline w200">
                <input type="text" class="layui-input" id="sale_day" name="sale_day" value="{$sale_day}" placeholder="请选择日期">
            </div>
            <div class="layui-inline">
                <button class="layui-btn" lay-submit lay-filter="Search"><i class="layui-icon">&#xe615;</i> 查询</button>
            </div>
            <span class="total">
                SKU个数三仓以上率：{:number_format(($storePercent[2]['count'] + $storePercent[3]['count']) / $storePercent[2]['countSum'], 4) * 100}% |
                SKU总数三仓以上率：{:number_format(($sumPercent[2]['goodsNum'] + $sumPercent[3]['goodsNum']) / $sumPercent[2]['goodsNumSum'], 4) * 100}%
            </span>
        </form>

        <table class="layui-table" lay-size="sm" style="width: 1000px">
            <colgroup>
                <col>
                <col>
                <col>
                <col>
                <col>
                <col>
            </colgroup>
            <thead>
            <tr>
                <th class="tr"></th>
                <th class="tr">数量数</th>
                <th class="tr">单仓率</th>
                <th class="tr">两仓率</th>
                <th class="tr">三仓率</th>
                <th class="tr">四仓率</th>
                <th class="tr">合计</th>
            </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="tr">SKU个数四仓率</td>
                    <td class="tr">{$storePercent.0.countSum}</td>
                    <td class="tr">{:number_format($storePercent[0]['count'] / $storePercent[0]['countSum'], 4) * 100}%</td>
                    <td class="tr">{:number_format($storePercent[1]['count'] / $storePercent[1]['countSum'], 4) * 100}%</td>
                    <td class="tr">{:number_format($storePercent[2]['count'] / $storePercent[2]['countSum'], 4) * 100}%</td>
                    <td class="tr">{:number_format($storePercent[3]['count'] / $storePercent[3]['countSum'], 4) * 100}%</td>
                    <td class="tr">{:number_format(($storePercent[0]['count'] + $storePercent[1]['count'] + $storePercent[2]['count'] + $storePercent[3]['count']) / $storePercent[3]['countSum'], 4) * 100}%</td>
                </tr>
                <tr>
                    <td class="tr">SKU总数四仓率</td>
                    <td class="tr">{$sumPercent.0.goodsNumSum}</td>
                    <td class="tr">{:number_format($sumPercent[0]['goodsNum'] / $sumPercent[0]['goodsNumSum'], 4) * 100}%</td>
                    <td class="tr">{:number_format($sumPercent[1]['goodsNum'] / $sumPercent[1]['goodsNumSum'], 4) * 100}%</td>
                    <td class="tr">{:number_format($sumPercent[2]['goodsNum'] / $sumPercent[2]['goodsNumSum'], 4) * 100}%</td>
                    <td class="tr">{:number_format($sumPercent[3]['goodsNum'] / $sumPercent[3]['goodsNumSum'], 4) * 100}%</td>
                    <td class="tr">{:number_format(($sumPercent[0]['goodsNum'] + $sumPercent[1]['goodsNum'] + $sumPercent[2]['goodsNum'] + $sumPercent[3]['goodsNum']) / $sumPercent[3]['goodsNumSum'], 4) * 100}%</td>
                </tr>
            </tbody>
        </table>

        <div class="layui-form">
            <div id="main" style="height:450px;width: 1000px;margin: 30px 0"></div>
        </div>
        <div class="layui-form">
            <div id="main2" style="height:450px;width: 1000px;margin: 30px 0"></div>
        </div>

        <button class="layui-btn layui-btn-normal" lay-submit lay-filter="Export">导出</button>
        <table class="layui-table" lay-size="sm" style="width: 1000px">
            <colgroup>
                <col width="10%">
                <col width="18%">
                <col width="18%">
                <col width="18%">
                <col width="18%">
                <col width="18%">
            </colgroup>
            <thead>
                <tr>
                    <th class="tc" colspan="6">SKU个数运营四仓率</th>
                </tr>
                <tr>
                    <th class="tr">运营人员</th>
                    <th class="tr">单仓率</th>
                    <th class="tr">两仓率</th>
                    <th class="tr">三仓率</th>
                    <th class="tr">四仓率</th>
                    <th class="tr">合计</th>
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
                <td class="tr">{:round($v.1 * 100 + $v.2 * 100 + $v.3 * 100 + $v.4 * 100, 1)}%</td>
            </tr>
            {/foreach}
            </tbody>
        </table>

        <button class="layui-btn layui-btn-normal" lay-submit lay-filter="Export2">导出</button>
        <table class="layui-table" lay-size="sm" style="width: 1000px">
            <colgroup>
                <col width="10%">
                <col width="18%">
                <col width="18%">
                <col width="18%">
                <col width="18%">
                <col width="18%">
            </colgroup>
            <thead>
                <tr>
                    <th class="tc" colspan="6">SKU总数运营四仓率</th>
                </tr>
                <tr>
                    <th class="tr">运营人员</th>
                    <th class="tr">单仓率</th>
                    <th class="tr">两仓率</th>
                    <th class="tr">三仓率</th>
                    <th class="tr">四仓率</th>
                    <th class="tr">合计</th>
                </tr>
            </thead>
            <tbody>
            {foreach name="sellerSumData" key="key" item="v"}
            <tr>
                <td class="tr">{$key}</td>
                <td class="tr">{$v.1 * 100}%</td>
                <td class="tr">{$v.2 * 100}%</td>
                <td class="tr">{$v.3 * 100}%</td>
                <td class="tr">{$v.4 * 100}%</td>
                <td class="tr">{:round($v.1 * 100 + $v.2 * 100 + $v.3 * 100 + $v.4 * 100, 1)}%</td>
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

        //
        form.on('submit(Export)', function(data){
            let date = $("#sale_day").val();
            location.href = "/Manage/SkuReport/four_warehouse_sku_export/sale_day/" + date;
            return false;
        });

        //
        form.on('submit(Export2)', function(data){
            let date = $("#sale_day").val();
            location.href = "/Manage/SkuReport/four_warehouse_sku_sum_export/sale_day/" + date;
            return false;
        });

        const myChart = echarts.init(document.getElementById("main"));
        myChart.setOption({
            title: {
                text: 'SKU个数四仓率折线图'
            },
            tooltip: {
                trigger: 'axis'
            },
            legend: {
                data: ['单仓率', '两仓率', '三仓率', '四仓率']
            },
            grid: {
                left: '3%',
                right: '4%',
                bottom: '3%',
                containLabel: true
            },
            xAxis: {
                type: 'category',
                boundaryGap: false,
                data: [{$date}]
            },
            yAxis: {
                type: 'value'
            },
            series: [
                {
                    name: '单仓率',
                    type: 'line',
                    data: [{$kindOne}]
                },
                {
                    name: '两仓率',
                    type: 'line',
                    data: [{$kindTwo}]
                },
                {
                    name: '三仓率',
                    type: 'line',
                    data: [{$kindThree}]
                },
                {
                    name: '四仓率',
                    type: 'line',
                    data: [{$kindFour}]
                }
            ]
        });

        const myChart2 = echarts.init(document.getElementById("main2"));
        myChart2.setOption({
            title: {
                text: 'SKU总数四仓率折线图'
            },
            tooltip: {
                trigger: 'axis'
            },
            legend: {
                data: ['单仓率', '两仓率', '三仓率', '四仓率']
            },
            grid: {
                left: '3%',
                right: '4%',
                bottom: '3%',
                containLabel: true
            },
            xAxis: {
                type: 'category',
                boundaryGap: false,
                data: [{$date2}]
            },
            yAxis: {
                type: 'value'
            },
            series: [
                {
                    name: '单仓率',
                    type: 'line',
                    data: [{$sumOne}]
                },
                {
                    name: '两仓率',
                    type: 'line',
                    data: [{$sumTwo}]
                },
                {
                    name: '三仓率',
                    type: 'line',
                    data: [{$sumThree}]
                },
                {
                    name: '四仓率',
                    type: 'line',
                    data: [{$sumFour}]
                }
            ]
        });

    });
</script>

{include file="public/footer" /}
