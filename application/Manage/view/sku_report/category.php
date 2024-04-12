
{include file="public/header" /}

<style>
    .product-img {position: absolute; right: 250px; top: 250px}
</style>
<!-- 主体内容 -->
<script src="/static/echarts/dist/echarts.min.js"></script>
<script src="/static/echarts/test/lib/jquery.min.js"></script>
<div class="layui-body" id="LAY_app_body">
    <div class="right">
        <a href="{:url('index')}" class="layui-btn layui-btn-danger layui-btn-sm fr"><i class="layui-icon">&#xe603;</i>返回上一页</a>
        <div class="title">类目销量饼状图</div>
        <form class="layui-form" method="get">
            <div class="layui-input-inline w200">
                <input type="text" class="layui-input" id="start" name="start" value="{$start}" placeholder="开始时间">
            </div>
            <div class="layui-input-inline w200">
                <input type="text" class="layui-input" id="end" name="end" value="{$end}" placeholder="结束时间">
            </div>
            <div class="layui-inline">
                <button class="layui-btn" lay-submit lay-filter="Search"><i class="layui-icon">&#xe615;</i> 查询</button>
            </div>
        </form>

        <div class="layui-form" style="display: flex">
            <div id="main_1" style="height:500px; width: 800px"></div>
            <div id="main_2" style="height:500px; width: 800px"></div>
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

        const category_1 = echarts.init(document.getElementById("main_1"));
        category_1.setOption({
            series: [
                {
                    type: 'pie',
                    data: {$category_1}
                }
            ]
        });

        const category_2 = echarts.init(document.getElementById("main_2"));
        category_2.setOption({
            series: [
                {
                    type: 'pie',
                    data: {$category_2}
                }
            ]
        });
    });
</script>

{include file="public/footer" /}
