
{include file="public/header" /}

<style>
    .pie-chart {margin-top: 32px}
</style>
<!-- 主体内容 -->
<script src="/static/echarts/dist/echarts.min.js"></script>
<script src="/static/echarts/test/lib/jquery.min.js"></script>
<div class="layui-body" id="LAY_app_body">
    <div class="right">
        <div class="title">FBM日库存统计饼状图<strong>(不包含RETURN、ACCESSORY)</strong></div>
        <form class="layui-form" method="get">
            <div class="layui-input-inline w200">
                <input type="text" class="layui-input" id="sale_day" name="sale_day" value="{$sale_day}" placeholder="请选择日期">
            </div>
            <div class="layui-inline">
                <button class="layui-btn" lay-submit lay-filter="Search"><i class="layui-icon">&#xe615;</i> 查询</button>
            </div>
        </form>

        <table class="layui-table" lay-size="sm" style="width: 1600px">
            <colgroup>
                <col>
                <col>
                <col>
                <col>
                <col>
            </colgroup>
            <thead>
            <tr>
                <th class="tc">上月库存</th>
                <th class="tc">今日库存</th>
                <th class="tc">上月销售库存</th>
                <th class="tc">月库存周转率</th>
                <th class="tc">年库存周转率</th>
            </tr>
            </thead>
            <tbody>
            <tr>
                <td class="tr">{$last_sum.0.value}</td>
                <td class="tr">{$sum.0.value}</td>
                <td class="tr">{$monthQty.0.qty}</td>
                <td class="tr">{$monthQty.0.qty / (($last_sum.0.value + $sum.0.value) / 2)|round=###,2}</td>
                <td class="tr">{$monthQty.0.qty / (($last_sum.0.value + $sum.0.value) / 2) * 12|round=###,2}</td>
            </tr>
            </tbody>
        </table>

        <div class="layui-form pie-chart" style="display: flex">
            <div id="main_1" style="height:500px; width: 800px"></div>
            <div id="main_2" style="height:500px; width: 800px"></div>
        </div>
        <div class="layui-form pie-chart2">
            <div id="main_3" style="height:500px; width: 1600px"></div>
        </div>
        <div class="layui-form pie-chart3">
            <div id="main_4" style="height:500px; width: 1600px"></div>
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

        function moneyFormat (num, decimal = 2, split = ',') {
            /*
              parameter：
              num：格式化目标数字
              decimal：保留几位小数，默认2位
              split：千分位分隔符，默认为,
              moneyFormat(123456789.87654321, 2, ',') // 123,456,789.88
            */
            function thousandFormat (num) {
                const len = num.length
                return len <= 3 ? num : thousandFormat(num.slice(0, len - 3)) + split + num.slice(len - 3, len)
            }
            if (isFinite(num)) { // num是数字
                if (num === 0) { // 为0
                    return num.toFixed(decimal)
                } else { // 非0
                    var res = ''
                    var dotIndex = String(num).indexOf('.')
                    if (dotIndex === -1) { // 整数
                        if (decimal === 0) {
                            res = thousandFormat(String(num))
                        } else {
                            res = thousandFormat(String(num)) + '.' + '0'.repeat(decimal)
                        }
                    } else { // 非整数
                        // js四舍五入 Math.round()：正数时4舍5入，负数时5舍6入
                        // Math.round(1.5) = 2
                        // Math.round(-1.5) = -1
                        // Math.round(-1.6) = -2
                        // 保留decimals位小数
                        const numStr = String((Math.round(num * Math.pow(10, decimal)) / Math.pow(10, decimal)).toFixed(decimal)) // 四舍五入，然后固定保留2位小数
                        const decimals = numStr.slice(dotIndex, dotIndex + decimal + 1) // 截取小数位
                        res = thousandFormat(numStr.slice(0, dotIndex)) + decimals
                    }
                    return res
                }
            } else {
                return '--'
            }
        }

        const category_1 = echarts.init(document.getElementById("main_1"));
        category_1.setOption({
            title: {
                text: '当日海外仓批次库存数量库龄统计饼状图(合计：{$sum.0.value|number_format}个)',
                // subtext: 'Fake Data',
                left: 'center'
            },
            tooltip: {
                trigger: 'item'
            },
            legend: {
                orient: 'vertical',
                left: 'left'
            },
            series: [
                {
                    type: 'pie',
                    data: {$storeList},
                    label: {
                        normal: {
                            show: true,
                            position: 'inner', // 数值显示在内部
                            formatter: function (c) {
                                return moneyFormat(c.value, 0);
                            }
                        },
                    },
                    emphasis: {
                        itemStyle: {
                            shadowBlur: 10,
                            shadowOffsetX: 0,
                            shadowColor: 'rgba(0, 0, 0, 0.5)'
                        }
                    }
                }
            ]
        });

        const category_2 = echarts.init(document.getElementById("main_2"));
        category_2.setOption({
            title: {
                text: '当日海外仓批次采购金额库龄统计饼状图(合计：{$sum.0.sum|number_format=###,2}元)',
                // subtext: 'Fake Data',
                left: 'center'
            },
            tooltip: {
                trigger: 'item'
            },
            legend: {
                orient: 'vertical',
                left: 'left'
            },
            series: [
                {
                    type: 'pie',
                    data: {$priceList},
                    label: {
                        normal: {
                            show: true,
                            position: 'inner', // 数值显示在内部
                            formatter: function (c) {
                                return moneyFormat(c.value.toString(), 3);
                            }
                        },
                    },
                    emphasis: {
                        itemStyle: {
                            shadowBlur: 10,
                            shadowOffsetX: 0,
                            shadowColor: 'rgba(0, 0, 0, 0.5)'
                        }
                    }
                }
            ]
        });

        const category_3 = echarts.init(document.getElementById("main_3"));
        category_3.setOption({
            title: {
                text: '当日与前两月批次库存数量库龄对比柱状图',
                // subtext: 'Fake Data',
                left: 'center'
            },
            legend: {
                orient: 'vertical',
                left: 'right'
            },
            tooltip: {},
            dataset: {
                source: {$storeData}
            },
            xAxis: { type: 'category' },
            yAxis: {},
            // Declare several bar series, each will be mapped
            // to a column of dataset.source by default.
            series: [{ type: 'bar', itemStyle: {color: '#FFC858'} }, { type: 'bar', itemStyle: {color: '#91CC75'} }, { type: 'bar', itemStyle: {color: '#5470C6'}  }]
        });

        category_3.on('click', function (params) {
            // 跳转到对应的页面
            window.location.href = "/Manage/SkuReport/inventory/date/" + params.seriesName + "/num/" + params.data[0] + ".html";
        });

        const category_4 = echarts.init(document.getElementById("main_4"));
        category_4.setOption({
            title: {
                text: '当日与前两月批次采购金额库龄对比柱状图',
                // subtext: 'Fake Data',
                left: 'center'
            },
            legend: {
                orient: 'vertical',
                left: 'right'
            },
            tooltip: {},
            dataset: {
                source: {$priceData}
            },
            xAxis: { type: 'category' },
            yAxis: {},
            // Declare several bar series, each will be mapped
            // to a column of dataset.source by default.
            series: [{ type: 'bar', itemStyle: {color: '#FFC858'} }, { type: 'bar', itemStyle: {color: '#91CC75'} }, { type: 'bar', itemStyle: {color: '#5470C6'}  }]
        });

        category_4.on('click', function (params) {
            // 跳转到对应的页面
            window.location.href = "/Manage/SkuReport/inventory/date/" + params.seriesName + "/num/" + params.data[0] + ".html";
        });
    });
</script>

{include file="public/footer" /}
