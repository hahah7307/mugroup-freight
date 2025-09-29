
{include file="public/header" /}

<style>
    .pie-chart {margin-top: 32px}
</style>
<!-- 主体内容 -->
<script src="/static/echarts/dist/echarts.min.js"></script>
<script src="/static/echarts/test/lib/jquery.min.js"></script>
<div class="layui-body" id="LAY_app_body">
    <div class="right">
        <div class="title">海外仓仓租</div>

        <div class="layui-form pie-chart2">
            <div id="main_3" style="height:1000px; width: 1800px"></div>
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

        const category_3 = echarts.init(document.getElementById("main_3"));
        category_3.setOption({
            title: {
                text: '良仓与乐歌账单仓租金额对比柱状图（美金）',
                // subtext: 'Fake Data',
                left: 'center'
            },
            color: [
                "#5470C6", "#91CC75", "#EE6666", "#FAC858",
                "#73C0DE", "#3BA272", "#FC8452", "#9A60B4",
                "#EA7CCC", "#2E91E5", "#E15F99", "#1CA71C",
                "#FB0D0D", "#DA16FF", "#222A2A", "#B68100"
            ],
            legend: {
                orient: 'vertical',
                left: 'right'
            },
            tooltip: {
                trigger: 'item',
                axisPointer: {
                    type: 'shadow'
                }
            },
            xAxis: { name: '月份', type: 'category', data: [{$month}] },
            yAxis: { name: '单位：美金', type: 'value' },
            // Declare several bar series, each will be mapped
            // to a column of dataset.source by default.
            series: [{$sum}]
        });
    });
</script>

{include file="public/footer" /}
