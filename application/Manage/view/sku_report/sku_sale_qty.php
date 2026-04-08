
{include file="public/header" /}

<style>
    .pie-chart2 {margin-top: 32px}
    /* 开启状态 */
    .layui-form-switch {
        background-color: #16b777;
        border-color: #16b777;
    }

    /* 关闭状态 */
    .layui-form-switch em {
        color: #fff !important;
    }

    /* 滑块 */
    .layui-form-switch i {
        background-color: #fff;
    }
</style>
<!-- 主体内容 -->
<script src="/static/echarts/dist/echarts.min.js"></script>
<script src="/static/echarts/test/lib/jquery.min.js"></script>
<div class="layui-body" id="LAY_app_body">
    <div class="right">
        <div class="title">产品销量年度对比柱状图</strong></div>
        <form class="layui-form" method="get">
            <div class="layui-input-inline w80">
                <input type="checkbox" name="switch" lay-skin="switch" lay-text="按月|按日" lay-filter="typeSwitch" {if condition="$type eq 1"}checked{/if}>
            </div>
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
            <div id="main_3" style="height:460px; width: 2000px"></div>
        </div>
        <div class="layui-form pie-chart2">
            <div id="main_4" style="height:460px; width: 2000px"></div>
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
            type: 'date'
        });

        // 显示日期选择器
        laydate.render({
            elem: '#end_date',
            type: 'date'
        });

        function moneyFormat (num, decimal = 2, split = ',') {
            /*
              parameter：
              num：格式化目标数字
              decimal：保留几位小数，默认2位
              split：千分位分隔符，默认为,
              moneyFormat(123456789.87654321, 2, ',') // 123,456,789.88
            */
            function thousandFormat (num) {
                const len = num.length
                return len <= 3 ? num : thousandFormat(num.slice(0, len - 3)) + split + num.slice(len - 3, len)
            }
            if (isFinite(num)) { // num是数字
                if (num === 0) { // 为0
                    return num.toFixed(decimal)
                } else { // 非0
                    var res = ''
                    var dotIndex = String(num).indexOf('.')
                    if (dotIndex === -1) { // 整数
                        if (decimal === 0) {
                            res = thousandFormat(String(num))
                        } else {
                            res = thousandFormat(String(num)) + '.' + '0'.repeat(decimal)
                        }
                    } else { // 非整数
                        // js四舍五入 Math.round()：正数时4舍5入，负数时5舍6入
                        // Math.round(1.5) = 2
                        // Math.round(-1.5) = -1
                        // Math.round(-1.6) = -2
                        // 保留decimals位小数
                        const numStr = String((Math.round(num * Math.pow(10, decimal)) / Math.pow(10, decimal)).toFixed(decimal)) // 四舍五入，然后固定保留2位小数
                        const decimals = numStr.slice(dotIndex, dotIndex + decimal + 1) // 截取小数位
                        res = thousandFormat(numStr.slice(0, dotIndex)) + decimals
                    }
                    return res
                }
            } else {
                return '--'
            }
        }

        const category_3 = echarts.init(document.getElementById("main_3"));
        category_3.setOption({
            title: {
                text: '产品销量对比柱状图（单位：个）',
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

        const category_4 = echarts.init(document.getElementById("main_4"));
        category_4.setOption({
            title: {
                text: '产品月日销对比柱状图（单位：个）',
                // subtext: 'Fake Data',
                left: 'center'
            },
            legend: {
                orient: 'vertical',
                left: 'right'
            },
            tooltip: {},
            dataset: {
                source: {$averageData}
            },
            xAxis: { type: 'category' },
            yAxis: {},
            // Declare several bar series, each will be mapped
            // to a column of dataset.source by default.
            series: [{ type: 'bar', itemStyle: {color: '#FFC858'} }, { type: 'bar', itemStyle: {color: '#91CC75'} }, { type: 'bar', itemStyle: {color: '#5470C6'}  }]
        });

        // category_3.on('click', function (params) {
        //     // 跳转到对应的页面
        //     window.location.href = "/Manage/SkuReport/inventory/date/" + params.seriesName + "/num/" + params.data[0] + ".html";
        // });

        form.on('switch(typeSwitch)', function(data){
            let type = data.elem.checked ? 1 : 0;
            // 当前URL
            let url = new URL(window.location.href);
            // 设置参数
            url.searchParams.set('type', type);
            // 跳转
            window.location.href = url.toString();
        });
    });
</script>

{include file="public/footer" /}
