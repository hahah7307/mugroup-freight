
{include file="public/header" /}

<style>
    .product-img {position: absolute; right: 250px; top: 120px}
</style>
<!-- 主体内容 -->
<script src="/static/echarts/dist/echarts.min.js"></script>
<script src="/static/echarts/test/lib/jquery.min.js"></script>
<div class="layui-body" id="LAY_app_body">
    <div class="right">
        <a href="{:url('four_zone')}" class="layui-btn layui-btn-danger layui-btn-sm fr"><i class="layui-icon">&#xe603;</i>返回上一页</a>
        <div class="title">{$product.productSku}（{$product.productTitle}）月度发货四区率柱状图</div>

        <div class="layui-form">
            <div class="product-img"><img src="{$product.productImages}" alt="" height="150"></div>
            <div id="main" style="height:1000px;"></div>
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

        const myChart = echarts.init(document.getElementById("main"));
        myChart.setOption({
            xAxis: {
                type: 'category',
                data: [{$month}]
            },
            yAxis: {
                type: 'value'
            },
            series: [
                {
                    data: [{$percent}],
                    type: 'bar',
                    label: {
                        normal: {
                            show: true,// 显示label
                            position: "inside", // 显示的label的位置
                        }
                    }
                }
            ]
        });
    });
</script>

{include file="public/footer" /}
