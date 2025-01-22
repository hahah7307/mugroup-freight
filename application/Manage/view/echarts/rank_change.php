
{include file="public/header" /}

<style>
    .product-img {position: absolute; right: 250px; top: 250px}
</style>
<!-- 主体内容 -->
<script src="/static/echarts/dist/echarts.min.js"></script>
<script src="/static/echarts/test/lib/jquery.min.js"></script>
<div class="layui-body" id="LAY_app_body">
    <div class="right">
        <a href="{:session('back_url', '', 'manage')}" class="layui-btn layui-btn-danger layui-btn-sm fr"><i class="layui-icon">&#xe603;</i>返回上一页</a>
        <div class="title">{$asin}小类排名折线图</div>

        <div class="layui-form">
            <div id="main" style="height:1000px;"></div>
        </div>
    </div>
</div>
<script>
    layui.use(['form', 'jquery', 'laydate'], function(){
        var $ = layui.jquery,
            form = layui.form,
            laydate = layui.laydate;

        const myChart = echarts.init(document.getElementById("main"));
        myChart.setOption({
            xAxis: {
                type: 'category',
                data: {$created_date}
            },
            yAxis: {
                type: 'value'
            },
            series: [
                {
                    data: {$small_rank},
                    type: 'line',
                    label: {
                        normal: {
                            show: true,// 显示label
                            position: "top", // 显示的label的位置
                        }
                    }
                }
            ]
        });
    });
</script>

{include file="public/footer" /}
