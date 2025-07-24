
{include file="public/header" /}

<style>
    .pie-chart {margin-top: 32px}
    .main_style {height:1000px; width: 2000px}
</style>
<!-- 主体内容 -->
<script src="/static/echarts/dist/echarts.min.js"></script>
<script src="/static/echarts/test/lib/jquery.min.js"></script>
<div class="layui-body" id="LAY_app_body">
    <div class="right">
        <div class="title">月度财报利润柱状图</div>
        <form class="layui-form" method="get">
            <div class="layui-input-inline w200">
                <input type="text" class="layui-input" name="keyword" value="{$keyword}" placeholder="SKU/运营">
            </div>
            <div class="layui-inline">
                <button class="layui-btn" lay-submit lay-filter="Search"><i class="layui-icon">&#xe615;</i> 查询</button>
            </div>
        </form>

        <div class="layui-tab" lay-filter="testTab">
            <ul class="layui-tab-title">
                <li class="layui-this">利润额</li>
                <li>销售额</li>
                <li>销量</li>
                <li>广告费</li>
                <li>仓储费</li>
            </ul>
            <div class="layui-tab-content">
                <div class="layui-tab-item layui-show">
                    <div class="layui-form pie-chart1">
                        <div id="main_1" class="main_style"></div>
                    </div>
                </div>
                <div class="layui-tab-item">
                    <div class="layui-form pie-chart2">
                        <div id="main_2" class="main_style"></div>
                    </div>
                </div>
                <div class="layui-tab-item">
                    <div class="layui-form pie-chart3">
                        <div id="main_3" class="main_style"></div>
                    </div>
                </div>
                <div class="layui-tab-item">
                    <div class="layui-form pie-chart4">
                        <div id="main_4" class="main_style"></div>
                    </div>
                </div>
                <div class="layui-tab-item">
                    <div class="layui-form pie-chart5">
                        <div id="main_5" class="main_style"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
    layui.use(['form', 'jquery', 'laydate', 'element'], function(){
        var $ = layui.jquery,
            form = layui.form,
            laydate = layui.laydate,
            element = layui.element;

        // 显示日期选择器
        laydate.render({
            elem: '#sale_day',
            type: 'date'
        });

        element.on('tab(testTab)', function(data){
            console.log('当前索引：'+ data.index);
        });

        const category_1 = echarts.init(document.getElementById("main_1"));
        category_1.setOption({
            title: {
                text: '月度财报利润额柱状图(单位：美金)',
                left: 'center'
            },
            tooltip: {
                trigger: 'axis',
                axisPointer: {
                    type: 'shadow'
                }
            },
            legend: {
                orient: 'horizontal',
                left: 'right'
            },
            grid: {
                left: '3%',
                right: '4%',
                bottom: '3%',
                containLabel: true
            },
            xAxis: [
                {
                    type: 'category',
                    data: [{$month}]
                }
            ],
            yAxis: [
                {
                    type: 'value'
                }
            ],
            series: [
                {$profitSeries}
            ]
        });

        const category_2 = echarts.init(document.getElementById("main_2"));
        category_2.setOption({
            title: {
                text: '月度财报销售额柱状图(单位：美金)',
                left: 'center'
            },
            tooltip: {
                trigger: 'axis',
                axisPointer: {
                    type: 'shadow'
                }
            },
            legend: {
                orient: 'horizontal',
                left: 'right'
            },
            grid: {
                left: '3%',
                right: '4%',
                bottom: '3%',
                containLabel: true
            },
            xAxis: [
                {
                    type: 'category',
                    data: [{$month}]
                }
            ],
            yAxis: [
                {
                    type: 'value'
                }
            ],
            series: [
                {$amountSeries}
            ]
        });

        const category_3 = echarts.init(document.getElementById("main_3"));
        category_3.setOption({
            title: {
                text: '月度财报销量柱状图(单位：美金)',
                left: 'center'
            },
            tooltip: {
                trigger: 'axis',
                axisPointer: {
                    type: 'shadow'
                }
            },
            legend: {
                orient: 'horizontal',
                left: 'right'
            },
            grid: {
                left: '3%',
                right: '4%',
                bottom: '3%',
                containLabel: true
            },
            xAxis: [
                {
                    type: 'category',
                    data: [{$month}]
                }
            ],
            yAxis: [
                {
                    type: 'value'
                }
            ],
            series: [
                {$qtySeries}
            ]
        });

        const category_4 = echarts.init(document.getElementById("main_4"));
        category_4.setOption({
            title: {
                text: '月度财报广告费柱状图(单位：美金)',
                left: 'center'
            },
            tooltip: {
                trigger: 'axis',
                axisPointer: {
                    type: 'shadow'
                }
            },
            legend: {
                orient: 'horizontal',
                left: 'right'
            },
            grid: {
                left: '3%',
                right: '4%',
                bottom: '3%',
                containLabel: true
            },
            xAxis: [
                {
                    type: 'category',
                    data: [{$month}]
                }
            ],
            yAxis: [
                {
                    type: 'value'
                }
            ],
            series: [
                {$adCostSeries}
            ]
        });

        const category_5 = echarts.init(document.getElementById("main_5"));
        category_5.setOption({
            title: {
                text: '月度财报仓储费柱状图(单位：美金)',
                left: 'center'
            },
            tooltip: {
                trigger: 'axis',
                axisPointer: {
                    type: 'shadow'
                }
            },
            legend: {
                orient: 'horizontal',
                left: 'right'
            },
            grid: {
                left: '3%',
                right: '4%',
                bottom: '3%',
                containLabel: true
            },
            xAxis: [
                {
                    type: 'category',
                    data: [{$month}]
                }
            ],
            yAxis: [
                {
                    type: 'value'
                }
            ],
            series: [
                {$warehouseRentSeries}
            ]
        });
    });
</script>

{include file="public/footer" /}
