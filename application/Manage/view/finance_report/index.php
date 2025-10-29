
{include file="public/header" /}

<style>
    .pie-chart {margin-top: 32px}
    .main_style {height:900px; width: 2000px}
    .product-group {margin-bottom: 12px}
    .see-detail {margin: 0 10px; padding: 10px 0 0; font-size: 24px}
    .hover-elem {cursor: pointer;}
</style>
<!-- 主体内容 -->
<script src="/static/echarts/dist/echarts.min.js"></script>
<script src="/static/echarts/test/lib/jquery.min.js"></script>
<div class="layui-body" id="LAY_app_body">
    <div class="right">
        <div class="title">月度财报利润柱状图</div>
        <form class="layui-form" method="get">
            <div class="layui-input-inline w200">
                <input type="text" class="layui-input" name="keyword" value="{$keyword}" placeholder="SKU/运营">
            </div>
            <div class="layui-input-inline w180">
                <div id="group_name"></div>
            </div>
            <div class="layui-input-inline hover-elem">
                <i class="layui-icon see-detail">&#xe60b;</i>
            </div>
            <div class="layui-inline">
                <button class="layui-btn" lay-submit lay-filter="Search"><i class="layui-icon">&#xe615;</i> 查询</button>
            </div>
        </form>

        <div class="layui-tab" lay-filter="testTab">
            <a href="{:url('sku_group')}" class="layui-btn product-group">产品组</a>
            <ul class="layui-tab-title">
                <li class="layui-this">利润额</li>
                <li>销售额</li>
                <li>销量</li>
                <li>广告费</li>
                <li>仓储费</li>
                <li>日包裹数</li>
                <li>日销售额</li>
            </ul>
            <div class="layui-tab-content">
                <div class="layui-tab-item layui-show">
                    <div class="layui-form pie-chart1">
                        <div id="main_1" class="main_style"></div>
                    </div>
                </div>
                <div class="layui-tab-item">
                    <div class="layui-form pie-chart2">
                        <div id="main_2" class="main_style"></div>
                    </div>
                </div>
                <div class="layui-tab-item">
                    <div class="layui-form pie-chart3">
                        <div id="main_3" class="main_style"></div>
                    </div>
                </div>
                <div class="layui-tab-item">
                    <div class="layui-form pie-chart4">
                        <div id="main_4" class="main_style"></div>
                    </div>
                </div>
                <div class="layui-tab-item">
                    <div class="layui-form pie-chart5">
                        <div id="main_5" class="main_style"></div>
                    </div>
                </div>
                <div class="layui-tab-item">
                    <div class="layui-form pie-chart6">
                        <div id="main_6" class="main_style"></div>
                    </div>
                </div>
                <div class="layui-tab-item">
                    <div class="layui-form pie-chart7">
                        <div id="main_7" class="main_style"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
    layui.use(['form', 'jquery', 'laydate', 'element'], function(){
        var $ = layui.jquery,
            form = layui.form,
            laydate = layui.laydate,
            xmSelect = layui.xmSelect,
            element = layui.element;

        // 启用搜索
        // var xmSelect = layui.xmSelect;
        // 确保 layui.use 已加载 xmSelect
        let select = xmSelect.render({
            el: '#group_name',
            name: 'group_name',
            radio: true,
            clickClose: true,
            filterable: true,
            remoteSearch: true,   // 开启远程搜索
            initValue: ['1'],
            remoteMethod: function(val, cb) {
                // val 是输入值
                // cb 是回调函数，用于返回数据
                $.get("{:url('Check/get_group_name_origin')}", {search: val, selected: ["{$group_name}"]}, function(res){
                    // cb(res.data); // 返回数组 [{name:'苹果',value:'1'}, ...]
                    let result = JSON.parse(res).filter(function(item){
                        return item.name.indexOf(val) !== -1;
                    });
                    cb(result);
                });
            }
        });

        var tipIndex = null;
        var hideTimer = null;

        // 触发元素：移入显示
        $('.hover-elem').on('mouseenter', function(){
            clearTimeout(hideTimer);
            var that = this;

            // 已有就不重复创建
            if (tipIndex !== null) return;

            tipIndex = layer.tips(
                '<div class="tip-content">{$sku_detail}</div>',
                that,
                {
                    tips: [3, '#333'],   // 朝向/颜色
                    time: 0,             // 0 = 不自动关闭
                    area: ['400px', 'auto'],
                    shade: 0,
                    anim: 5,
                    success: function(layero){
                        // 关键：在悬浮层本身也绑定进入/离开
                        layero.on('mouseenter', function(){
                            clearTimeout(hideTimer);
                        });
                        layero.on('mouseleave', function(){
                            hideTimer = setTimeout(function(){
                                layer.close(tipIndex);
                                tipIndex = null;
                            }, 150); // 150ms 缓冲，允许鼠标「跨过空隙」
                        });
                    }
                }
            );
        });

        // 触发元素：移出时不要立刻关，给点时间让鼠标移动到层上
        $('.hover-elem').on('mouseleave', function(){
            hideTimer = setTimeout(function(){
                if (tipIndex !== null){
                    layer.close(tipIndex);
                    tipIndex = null;
                }
            }, 150);
        });

        // 显示日期选择器
        laydate.render({
            elem: '#sale_day',
            type: 'date'
        });

        element.on('tab(testTab)', function(data){
            console.log('当前索引：'+ data.index);
        });

        const category_1 = echarts.init(document.getElementById("main_1"));
        category_1.setOption({
            title: {
                text: '月度财报利润额柱状图(单位：美金)',
                left: 'center'
            },
            tooltip: {
                trigger: 'item',
                axisPointer: {
                    type: 'shadow'
                }
            },
            legend: {
                orient: 'horizontal',
                left: 'right'
            },
            grid: {
                left: '3%',
                right: '4%',
                bottom: '3%',
                containLabel: true
            },
            xAxis: [
                {
                    type: 'category',
                    data: [{$month}]
                }
            ],
            yAxis: [
                {
                    type: 'value'
                }
            ],
            series: [
                {$profitSeries}
            ]
        });

        const category_2 = echarts.init(document.getElementById("main_2"));
        category_2.setOption({
            title: {
                text: '月度财报销售额柱状图(单位：美金)',
                left: 'center'
            },
            tooltip: {
                trigger: 'item',
                axisPointer: {
                    type: 'shadow'
                }
            },
            legend: {
                orient: 'horizontal',
                left: 'right'
            },
            grid: {
                left: '3%',
                right: '4%',
                bottom: '3%',
                containLabel: true
            },
            xAxis: [
                {
                    type: 'category',
                    data: [{$month}]
                }
            ],
            yAxis: [
                {
                    type: 'value'
                }
            ],
            series: [
                {$amountSeries}
            ]
        });

        const category_3 = echarts.init(document.getElementById("main_3"));
        category_3.setOption({
            title: {
                text: '月度财报销量柱状图(单位：美金)',
                left: 'center'
            },
            tooltip: {
                trigger: 'item',
                axisPointer: {
                    type: 'shadow'
                }
            },
            legend: {
                orient: 'horizontal',
                left: 'right'
            },
            grid: {
                left: '3%',
                right: '4%',
                bottom: '3%',
                containLabel: true
            },
            xAxis: [
                {
                    type: 'category',
                    data: [{$month}]
                }
            ],
            yAxis: [
                {
                    type: 'value'
                }
            ],
            series: [
                {$qtySeries}
            ]
        });

        const category_4 = echarts.init(document.getElementById("main_4"));
        category_4.setOption({
            title: {
                text: '月度财报广告费柱状图(单位：美金)',
                left: 'center'
            },
            tooltip: {
                trigger: 'item',
                axisPointer: {
                    type: 'shadow'
                }
            },
            legend: {
                orient: 'horizontal',
                left: 'right'
            },
            grid: {
                left: '3%',
                right: '4%',
                bottom: '3%',
                containLabel: true
            },
            xAxis: [
                {
                    type: 'category',
                    data: [{$month}]
                }
            ],
            yAxis: [
                {
                    type: 'value'
                }
            ],
            series: [
                {$adCostSeries}
            ]
        });

        const category_5 = echarts.init(document.getElementById("main_5"));
        category_5.setOption({
            title: {
                text: '月度财报仓储费柱状图(单位：美金)',
                left: 'center'
            },
            tooltip: {
                trigger: 'item',
                axisPointer: {
                    type: 'shadow'
                }
            },
            legend: {
                orient: 'horizontal',
                left: 'right'
            },
            grid: {
                left: '3%',
                right: '4%',
                bottom: '3%',
                containLabel: true
            },
            xAxis: [
                {
                    type: 'category',
                    data: [{$month}]
                }
            ],
            yAxis: [
                {
                    type: 'value'
                }
            ],
            series: [
                {$warehouseRentSeries}
            ]
        });

        const category_6 = echarts.init(document.getElementById("main_6"));
        category_6.setOption({
            title: {
                text: '月度财报日包裹数柱状图(单位：个)',
                left: 'center'
            },
            tooltip: {
                trigger: 'item',
                axisPointer: {
                    type: 'shadow'
                }
            },
            legend: {
                orient: 'horizontal',
                left: 'right'
            },
            grid: {
                left: '3%',
                right: '4%',
                bottom: '3%',
                containLabel: true
            },
            xAxis: [
                {
                    type: 'category',
                    data: [{$month}]
                }
            ],
            yAxis: [
                {
                    type: 'value'
                }
            ],
            series: [
                {$dayQtySeries}
            ]
        });

        const category_7 = echarts.init(document.getElementById("main_7"));
        category_7.setOption({
            title: {
                text: '月度财报日销售额柱状图(单位：美金)',
                left: 'center'
            },
            tooltip: {
                trigger: 'item',
                axisPointer: {
                    type: 'shadow'
                }
            },
            legend: {
                orient: 'horizontal',
                left: 'right'
            },
            grid: {
                left: '3%',
                right: '4%',
                bottom: '3%',
                containLabel: true
            },
            xAxis: [
                {
                    type: 'category',
                    data: [{$month}]
                }
            ],
            yAxis: [
                {
                    type: 'value'
                }
            ],
            series: [
                {$dayAmountSeries}
            ]
        });
    });
</script>

{include file="public/footer" /}
