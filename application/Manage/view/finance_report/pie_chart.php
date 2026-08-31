
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
        <div class="title">月度财报分析饼状图</div>
        <form class="layui-form" method="get">
            <div class="layui-input-inline w200">
                <input type="text" class="layui-input" id="month1" name="month1" value="{$month1}" placeholder="开始月份">
            </div>
            <div class="layui-input-inline w200">
                <input type="text" class="layui-input" id="month2" name="month2" value="{$month2}" placeholder="结束月份">
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
            </ul>
            <div class="layui-tab-content">
                <div class="layui-tab-item layui-show">
                    <div class="layui-form pie-chart1">
                        <div class="layui-form pie-chart" style="display: flex">
                            <div id="main_1" style="height:600px; width: 800px"></div>
                            <div id="main_2" style="height:600px; width: 800px"></div>
                        </div>
                        <div class="layui-form" style="display: flex">
                            <div id="main_3" style="height:600px; width: 800px"></div>
                            <div id="main_4" style="height:600px; width: 800px"></div>
                        </div>
                        <div class="layui-form" style="display: flex">
                            <div id="main_5" style="height:600px; width: 800px"></div>
                            <div id="main_6" style="height:600px; width: 800px"></div>
                        </div>
                    </div>
                </div>
                <div class="layui-tab-item">
                    <div class="layui-form pie-chart2">
                        <div class="layui-form pie-chart" style="display: flex">
                            <div id="main_7" style="height:600px; width: 800px"></div>
                        </div>
                        <div class="layui-form" style="display: flex">
                            <div id="main_8" style="height:600px; width: 800px"></div>
                        </div>
                        <div class="layui-form" style="display: flex">
                            <div id="main_9" style="height:600px; width: 800px"></div>
                        </div>
                    </div>
                </div>
                <div class="layui-tab-item">
                    <div class="layui-form pie-chart2">
                        <div class="layui-form pie-chart" style="display: flex">
                            <div id="main_10" style="height:600px; width: 800px"></div>
                        </div>
                        <div class="layui-form" style="display: flex">
                            <div id="main_11" style="height:600px; width: 800px"></div>
                        </div>
                        <div class="layui-form" style="display: flex">
                            <div id="main_12" style="height:600px; width: 800px"></div>
                        </div>
                    </div>
                </div>
            </div>
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
            elem: '#month1',
            type: 'month'
        });

        laydate.render({
            elem: '#month2',
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
                text: '一级类目利润饼状图(点击跳转二级类目)',
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
            window.location.href = "/Manage/FinanceReport/pie_chart/category/" + params.name + ".html?month1={$month1}&month2={$month2}";
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
                text: '一级类目亏损饼状图(点击跳转二级类目)({$ratio_1 * 100}%)',
                left: 'center'
            },
            series: [
                {
                    type: 'pie',
                    radius: "{$ratio_1_show}",
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

        category_2.on('click', function (params) {
            // 跳转到对应的页面
            window.location.href = "/Manage/FinanceReport/pie_chart/category/" + params.name + ".html?month1={$month1}&month2={$month2}";
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
                text: '二级类目利润饼状图(点击弹出产品详情)',
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

        category_3.on('click', function (params) {
            layer.open({
                type: 2,
                title: false,
                area: ['50%', '80%'],
                content: "/Manage/FinanceReport/sku_detail.html?group_name=" + params.name,
            });
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
                text: '二级类目亏损饼状图(点击弹出产品详情)({$ratio_3 * 100}%)',
                left: 'center'
            },
            series: [
                {
                    type: 'pie',
                    radius: "{$ratio_3_show}",
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

        category_4.on('click', function (params) {
            layer.open({
                type: 2,
                title: false,
                area: ['50%', '80%'],
                content: "/Manage/FinanceReport/sku_detail.html?group_name=" + params.name,
            });
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
                text: '{$category}类目利润饼状图(点击弹出产品详情)',
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

        category_5.on('click', function (params) {
            layer.open({
                type: 2,
                title: false,
                area: ['50%', '80%'],
                content: "/Manage/FinanceReport/sku_detail.html?group_name=" + params.name,
            });
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
                text: '{$category}类目亏损饼状图(点击弹出产品详情)({$ratio_5 * 100}%)',
                left: 'center'
            },
            series: [
                {
                    type: 'pie',
                    radius: "{$ratio_5_show}",
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

        category_6.on('click', function (params) {
            layer.open({
                type: 2,
                title: false,
                area: ['50%', '80%'],
                content: "/Manage/FinanceReport/sku_detail.html?group_name=" + params.name,
            });
        });

        // ****************************************************************************************************************************************

        const category_7 = echarts.init(document.getElementById("main_7"));
        category_7.setOption({
            tooltip: {
                trigger: 'item',
                formatter: function (params) {
                    // params.value 是数值
                    return `${params.name}: ${parseFloat(params.value).toLocaleString()} (${params.percent}%)`;
                }
            },
            title: {
                text: '一级类目销售额饼状图(点击跳转二级类目)',
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
                    data: {$amount_1}
                }
            ]
        });

        category_7.on('click', function (params) {
            // 跳转到对应的页面
            window.location.href = "/Manage/FinanceReport/pie_chart/category/" + params.name + ".html?month={$month}";
        });

        const category_8 = echarts.init(document.getElementById("main_8"));
        category_8.setOption({
            tooltip: {
                trigger: 'item',
                formatter: function (params) {
                    // params.value 是数值
                    return `${params.name}: ${parseFloat(params.value).toLocaleString()} (${params.percent}%)`;
                }
            },
            title: {
                text: '二级类目销售额饼状图(点击弹出产品详情)',
                left: 'center',
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
                    data: {$amount_3}
                }
            ]
        });

        category_8.on('click', function (params) {
            layer.open({
                type: 2,
                title: false,
                area: ['50%', '80%'],
                content: "/Manage/FinanceReport/sku_detail.html?group_name=" + params.name,
            });
        });

        const category_9 = echarts.init(document.getElementById("main_9"));
        category_9.setOption({
            tooltip: {
                trigger: 'item',
                formatter: function (params) {
                    // params.value 是数值
                    return `${params.name}: ${parseFloat(params.value).toLocaleString()} (${params.percent}%)`;
                }
            },
            title: {
                text: '{$category}类目销售额饼状图(点击弹出产品详情)',
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
                    data: {$amount_5}
                }
            ]
        });

        category_9.on('click', function (params) {
            layer.open({
                type: 2,
                title: false,
                area: ['50%', '80%'],
                content: "/Manage/FinanceReport/sku_detail.html?group_name=" + params.name,
            });
        });

        // ****************************************************************************************************************************************

        const category_10 = echarts.init(document.getElementById("main_10"));
        category_10.setOption({
            tooltip: {
                trigger: 'item',
                formatter: function (params) {
                    // params.value 是数值
                    return `${params.name}: ${parseFloat(params.value).toLocaleString()} (${params.percent}%)`;
                }
            },
            title: {
                text: '一级类目销量饼状图(点击跳转二级类目)',
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
                    data: {$qty_1}
                }
            ]
        });

        category_10.on('click', function (params) {
            // 跳转到对应的页面
            window.location.href = "/Manage/FinanceReport/pie_chart/category/" + params.name + ".html?month={$month}";
        });

        const category_11 = echarts.init(document.getElementById("main_11"));
        category_11.setOption({
            tooltip: {
                trigger: 'item',
                formatter: function (params) {
                    // params.value 是数值
                    return `${params.name}: ${parseFloat(params.value).toLocaleString()} (${params.percent}%)`;
                }
            },
            title: {
                text: '二级类目销量饼状图(点击弹出产品详情)',
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
                    data: {$qty_3}
                }
            ]
        });

        category_11.on('click', function (params) {
            layer.open({
                type: 2,
                title: false,
                area: ['50%', '80%'],
                content: "/Manage/FinanceReport/sku_detail.html?group_name=" + params.name,
            });
        });

        const category_12 = echarts.init(document.getElementById("main_12"));
        category_12.setOption({
            tooltip: {
                trigger: 'item',
                formatter: function (params) {
                    // params.value 是数值
                    return `${params.name}: ${parseFloat(params.value).toLocaleString()} (${params.percent}%)`;
                }
            },
            title: {
                text: '{$category}类目销量饼状图(点击弹出产品详情)',
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
                    data: {$qty_5}
                }
            ]
        });

        category_12.on('click', function (params) {
            layer.open({
                type: 2,
                title: false,
                area: ['50%', '80%'],
                content: "/Manage/FinanceReport/sku_detail.html?group_name=" + params.name,
            });
        });
    });
</script>

{include file="public/footer" /}
