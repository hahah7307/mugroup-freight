
{include file="public/header" /}

<!-- 主体内容 -->
<script src="/static/echarts/dist/echarts.min.js"></script>
<script src="/static/echarts/test/lib/jquery.min.js"></script>
<div class="layui-body" id="LAY_app_body">
    <div class="right">
        <div class="title">利润额对比柱状图</strong></div>
        <form class="layui-form" method="get">
            <div class="layui-input-inline w200">
                <textarea name="sku" placeholder="SKU，多个用回车键分割" class="layui-textarea">{$sku}</textarea>
            </div>
            <div class="layui-input-inline w200">
                <input type="text" class="layui-input" name="name" value="{$name}" placeholder="品名">
            </div>
            <div class="layui-input-inline w200">
                <input type="text" class="layui-input" id="start_date" name="start_date" value="{$start_date}" placeholder="请选择日期">
            </div>
            <div class="layui-input-inline w200">
                <input type="text" class="layui-input" id="end_date" name="end_date" value="{$end_date}" placeholder="请选择日期">
            </div>
            <div class="layui-inline">
                <button class="layui-btn" lay-submit lay-filter="Search"><i class="layui-icon">&#xe615;</i> 查询</button>
            </div>
        </form>

        <div class="layui-form pie-chart2">
            <div id="main_3" style="height:960px; width: 2000px"></div>
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
            elem: '#start_date',
            type: 'month'
        });

        // 显示日期选择器
        laydate.render({
            elem: '#end_date',
            type: 'month'
        });

        const category_3 = echarts.init(document.getElementById("main_3"));
        category_3.setOption({
            title: {
                text: '利润额对比柱状图（单位：美金）',
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
    });
</script>

{include file="public/footer" /}
