
{include file="public/header" /}

<style>
    .pie-chart {margin-top: 32px}
</style>
<style>
    .product-img {position: absolute; right: 250px; top: 250px}
</style>
<!-- 主体内容 -->
<script src="/static/echarts/dist/echarts.min.js"></script>
<script src="/static/echarts/test/lib/jquery.min.js"></script>
<div class="layui-body" id="LAY_app_body">
    <div class="right">
        <a href="{:url('index')}" class="layui-btn layui-btn-danger layui-btn-sm fr"><i class="layui-icon">&#xe603;</i>返回上一页</a>
        <div class="title">饼状图</div>
        <form class="layui-form" method="get">
            <div class="layui-input-inline w200">
                <input type="text" class="layui-input" id="month" name="month" value="{$month}" placeholder="月份">
            </div>
            <div class="layui-inline">
                <button class="layui-btn" lay-submit lay-filter="Search"><i class="layui-icon">&#xe615;</i> 查询</button>
            </div>
        </form>

        <div class="layui-form pie-chart" style="display: flex">
            <div id="main_1" style="height:500px; width: 800px"></div>
            <div id="main_2" style="height:500px; width: 800px"></div>
        </div>
        <div class="layui-form" style="display: flex">
            <div id="main_3" style="height:500px; width: 800px"></div>
            <div id="main_4" style="height:500px; width: 800px"></div>
        </div>
        <div class="layui-form" style="display: flex">
            <div id="main_5" style="height:500px; width: 800px"></div>
            <div id="main_6" style="height:500px; width: 800px"></div>
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
            elem: '#month',
            type: 'month'
        });

        const category_1 = echarts.init(document.getElementById("main_1"));
        category_1.setOption({
            tooltip: {
                trigger: 'item',
                formatter: function (params) {
                    // params.value 是数值
                    return `${params.name}: ${parseFloat(params.value).toLocaleString()} (${params.percent}%)`;
                }
            },
            title: {
                text: '一级类目利润饼状图',
                left: 'center'
            },
            series: [
                {
                    type: 'pie',
                    emphasis: {
                        scale: true, // 是否放大
                            scaleSize: 10, // 放大比例
                            itemStyle: {
                            shadowBlur: 20, // 阴影大小
                                shadowOffsetX: 0,
                                shadowColor: 'rgba(0, 0, 0, 0.5)' // 阴影颜色
                        },
                        label: {
                            show: true,
                                fontSize: 18,
                                fontWeight: 'bold',
                                color: '#333'
                        }
                    },
                    animationType: 'scale',
                    animationEasing: 'elasticOut',
                    animationDelay: function (idx) {
                        return Math.random() * 200;
                    },
                    data: {$profit_1}
                }
            ]
        });

        category_1.on('click', function (params) {
            // 跳转到对应的页面
            window.location.href = "/Manage/FinanceReport/pie_chart/category/" + params.name + ".html?month={$month}";
        });

        const category_2 = echarts.init(document.getElementById("main_2"));
        category_2.setOption({
            tooltip: {
                trigger: 'item',
                formatter: function (params) {
                    // params.value 是数值
                    return `${params.name}: ${parseFloat(params.value).toLocaleString()} (${params.percent}%)`;
                }
            },
            title: {
                text: '一级类目亏损饼状图',
                left: 'center'
            },
            series: [
                {
                    type: 'pie',
                    emphasis: {
                        scale: true, // 是否放大
                        scaleSize: 10, // 放大比例
                        itemStyle: {
                            shadowBlur: 20, // 阴影大小
                            shadowOffsetX: 0,
                            shadowColor: 'rgba(0, 0, 0, 0.5)' // 阴影颜色
                        },
                        label: {
                            show: true,
                            fontSize: 18,
                            fontWeight: 'bold',
                            color: '#333'
                        }
                    },
                    animationType: 'scale',
                    animationEasing: 'elasticOut',
                    animationDelay: function (idx) {
                        return Math.random() * 200;
                    },
                    data: {$profit_2}
                }
            ]
        });

        const category_3 = echarts.init(document.getElementById("main_3"));
        category_3.setOption({
            tooltip: {
                trigger: 'item',
                formatter: function (params) {
                    // params.value 是数值
                    return `${params.name}: ${parseFloat(params.value).toLocaleString()} (${params.percent}%)`;
                }
            },
            title: {
                text: '二级类目利润饼状图',
                left: 'center'
            },
            series: [
                {
                    type: 'pie',
                    emphasis: {
                        scale: true, // 是否放大
                        scaleSize: 10, // 放大比例
                        itemStyle: {
                            shadowBlur: 20, // 阴影大小
                            shadowOffsetX: 0,
                            shadowColor: 'rgba(0, 0, 0, 0.5)' // 阴影颜色
                        },
                        label: {
                            show: true,
                            fontSize: 18,
                            fontWeight: 'bold',
                            color: '#333'
                        }
                    },
                    animationType: 'scale',
                    animationEasing: 'elasticOut',
                    animationDelay: function (idx) {
                        return Math.random() * 200;
                    },
                    data: {$profit_3}
                }
            ]
        });

        const category_4 = echarts.init(document.getElementById("main_4"));
        category_4.setOption({
            tooltip: {
                trigger: 'item',
                formatter: function (params) {
                    // params.value 是数值
                    return `${params.name}: ${parseFloat(params.value).toLocaleString()} (${params.percent}%)`;
                }
            },
            title: {
                text: '二级类目亏损饼状图',
                left: 'center'
            },
            series: [
                {
                    type: 'pie',
                    emphasis: {
                        scale: true, // 是否放大
                        scaleSize: 10, // 放大比例
                        itemStyle: {
                            shadowBlur: 20, // 阴影大小
                            shadowOffsetX: 0,
                            shadowColor: 'rgba(0, 0, 0, 0.5)' // 阴影颜色
                        },
                        label: {
                            show: true,
                            fontSize: 18,
                            fontWeight: 'bold',
                            color: '#333'
                        }
                    },
                    animationType: 'scale',
                    animationEasing: 'elasticOut',
                    animationDelay: function (idx) {
                        return Math.random() * 200;
                    },
                    data: {$profit_4}
                }
            ]
        });

        const category_5 = echarts.init(document.getElementById("main_5"));
        category_5.setOption({
            tooltip: {
                trigger: 'item',
                formatter: function (params) {
                    // params.value 是数值
                    return `${params.name}: ${parseFloat(params.value).toLocaleString()} (${params.percent}%)`;
                }
            },
            title: {
                text: '{$category}类目利润饼状图',
                left: 'center'
            },
            series: [
                {
                    type: 'pie',
                    emphasis: {
                        scale: true, // 是否放大
                        scaleSize: 10, // 放大比例
                        itemStyle: {
                            shadowBlur: 20, // 阴影大小
                            shadowOffsetX: 0,
                            shadowColor: 'rgba(0, 0, 0, 0.5)' // 阴影颜色
                        },
                        label: {
                            show: true,
                            fontSize: 18,
                            fontWeight: 'bold',
                            color: '#333'
                        }
                    },
                    animationType: 'scale',
                    animationEasing: 'elasticOut',
                    animationDelay: function (idx) {
                        return Math.random() * 200;
                    },
                    data: {$profit_5}
                }
            ]
        });

        const category_6 = echarts.init(document.getElementById("main_6"));
        category_6.setOption({
            tooltip: {
                trigger: 'item',
                formatter: function (params) {
                    // params.value 是数值
                    return `${params.name}: ${parseFloat(params.value).toLocaleString()} (${params.percent}%)`;
                }
            },
            title: {
                text: '{$category}类目亏损饼状图',
                left: 'center'
            },
            series: [
                {
                    type: 'pie',
                    emphasis: {
                        scale: true, // 是否放大
                        scaleSize: 10, // 放大比例
                        itemStyle: {
                            shadowBlur: 20, // 阴影大小
                            shadowOffsetX: 0,
                            shadowColor: 'rgba(0, 0, 0, 0.5)' // 阴影颜色
                        },
                        label: {
                            show: true,
                            fontSize: 18,
                            fontWeight: 'bold',
                            color: '#333'
                        }
                    },
                    animationType: 'scale',
                    animationEasing: 'elasticOut',
                    animationDelay: function (idx) {
                        return Math.random() * 200;
                    },
                    data: {$profit_6}
                }
            ]
        });

        // const category_3 = echarts.init(document.getElementById("main_3"));
        // category_3.setOption({
        //     title: {
        //         text: "{$category}类目销量饼状图",
        //         left: 'center'
        //     },
        //     series: [
        //         {
        //             type: 'pie',
        //             data: {$category_3}
        //         }
        //     ]
        // });
    });
</script>

{include file="public/footer" /}
