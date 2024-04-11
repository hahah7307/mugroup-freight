
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
        <div class="title">{$product.productSku}（{$product.productTitle}）销量柱状图</div>
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

        const data = {$data};
        const myChart = echarts.init(document.getElementById("main"));
        myChart.setOption({
            legend: {
                orient: 'vertical',
                right: 400,
                top: 'top',
                data: ['Amazon', 'Walmart', 'Wayfair']
            },
            grid: {
                left: '10%',
                top: '-50%' //柱状图上层有透明柱，需要向下减去一半
            },
            xAxis: {
                data: data.map(item => item.month)
            },
            yAxis: {},
            series: [
                {
                    name: "Amazon",
                    data: data.map(item => item.amazon),
                    type: 'bar',
                    stack: 'x',
                    label: {
                        normal: {
                            show: true,// 显示label
                            position: "inside", // 显示的label的位置
                        }
                    }
                },
                {
                    name: "Walmart",
                    data: data.map(item => item.walmart),
                    type: 'bar',
                    stack: 'x',
                    label: {
                        normal: {
                            show: true,// 显示label
                            position: "inside", // 显示的label的位置
                        }
                    }
                },
                {
                    name: "Wayfair",
                    data: data.map(item => item.wayfairnew),
                    type: 'bar',
                    stack: 'x',
                    label: {
                        normal: {
                            show: true,// 显示label
                            position: "inside", // 显示的label的位置
                        }
                    }
                },
                {
                    data: [{$qty}],
                    type: 'bar',
                    stack: 'x',
                    label: {
                        normal: {
                            show: true,
                            position: 'insideBottom',
                            formatter: '{c}',         // 显示的总数
                            textStyle: { color: 'green', fontsize: 36 }
                        }
                    },
                    itemStyle: {
                        normal: {
                            color: 'rgba(128, 128, 128, 0)'      // 柱状图颜色设为透明
                        }
                    }
                }
            ]
        });
    });
</script>

{include file="public/footer" /}
