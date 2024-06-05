
{include file="public/header" /}

<!-- 主体内容 -->
<script src="/static/echarts/dist/echarts.min.js"></script>
<script src="/static/echarts/test/lib/jquery.min.js"></script>
<div class="layui-body" id="LAY_app_body">
    <div class="right">
        <a href="{:session('back_url', '', 'manage')}" class="layui-btn layui-btn-danger layui-btn-sm fr"><i class="layui-icon">&#xe603;</i>返回上一页</a>
        <div class="title"><strong>{$sku}</strong> Wayfair平台销量、增长率折线图</div>
        <form class="layui-form" method="get">
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
        <div class="layui-form">
            <div id="main_1" style="height:500px; width: 1800px"></div>
        </div>
        <div class="layui-form">
            <div id="main_2" style="height:500px; width: 1800px"></div>
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

        const category_1 = echarts.init(document.getElementById("main_1"));
        category_1.setOption({
            title: {
                text: '销量折线图(单位：个)',
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
            xAxis: {
                type: 'category',
                boundaryGap: false,
                data: ["{$month}"]
            },
            yAxis: {
                type: 'value'
            },
            series: [
                {
                    data: [{$qty}],
                    type: 'line',
                    areaStyle: {}
                }
            ]
        });

        const category_2 = echarts.init(document.getElementById("main_2"));
        category_2.setOption({
            title: {
                text: '销量增长率折线图',
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
            xAxis: {
                type: 'category',
                data: ["{$month2}"]
            },
            yAxis: {
                type: 'value'
            },
            series: [
                {
                    data: [{$rate}],
                    type: 'line'
                }
            ]
        });
    });
</script>

{include file="public/footer" /}
