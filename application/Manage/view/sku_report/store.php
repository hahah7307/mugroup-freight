
{include file="public/header" /}

<style>
    .pie-chart {margin-top: 32px}
</style>
<!-- 主体内容 -->
<script src="/static/echarts/dist/echarts.min.js"></script>
<script src="/static/echarts/test/lib/jquery.min.js"></script>
<div class="layui-body" id="LAY_app_body">
    <div class="right">
        <div class="title">日库存统计饼状图</div>
        <form class="layui-form" method="get">
            <div class="layui-input-inline w200">
                <input type="text" class="layui-input" id="sale_day" name="sale_day" value="{$sale_day}" placeholder="请选择日期">
            </div>
            <div class="layui-inline">
                <button class="layui-btn" lay-submit lay-filter="Search"><i class="layui-icon">&#xe615;</i> 查询</button>
            </div>
        </form>

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

        const category_1 = echarts.init(document.getElementById("main_1"));
        category_1.setOption({
            title: {
                text: '当日海外仓批次库存库龄统计饼状图',
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
                            formatter: '{c}', // 格式化数值百分比输出
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
                text: '当日海外仓批次采购金额库龄统计饼状图',
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
                            formatter: '{c}', // 格式化数值百分比输出
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
                text: '当日与前两月批次库存库龄对比柱状图',
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
            series: [{ type: 'bar' }, { type: 'bar' }, { type: 'bar' }]
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
            series: [{ type: 'bar' }, { type: 'bar' }, { type: 'bar' }]
        });
    });
</script>

{include file="public/footer" /}
