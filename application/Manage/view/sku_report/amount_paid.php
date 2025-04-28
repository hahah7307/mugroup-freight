
{include file="public/header" /}

<style>
    .pie-chart {margin-top: 32px}
</style>
<!-- 主体内容 -->
<script src="/static/echarts/dist/echarts.min.js"></script>
<script src="/static/echarts/test/lib/jquery.min.js"></script>
<div class="layui-body" id="LAY_app_body">
    <div class="right">
        <div class="title">各平台销售月柱状图</div>
        <form class="layui-form" method="get">
            平台：
            <div class="layui-inline w120">
                <select name="platform" lay-verify="">
                    <option value="amazon" {if condition="$platform eq 'amazon'"}selected{/if}>Amazon</option>
                    <option value="wayfairnew" {if condition="$platform eq 'wayfairnew'"}selected{/if}>Wayfair</option>
                    <option value="walmart" {if condition="$platform eq 'walmart'"}selected{/if}>Walmart</option>
                    <option value="shein" {if condition="$platform eq 'shein'"}selected{/if}>Shein</option>
                    <option value="ebay" {if condition="$platform eq 'ebay'"}selected{/if}>Ebay</option>
                    <option value="shopify" {if condition="$platform eq 'shopify'"}selected{/if}>Shopify</option>
                    <option value="tiktok" {if condition="$platform eq 'tiktok'"}selected{/if}>Tiktok</option>
                    <option value="semitemu" {if condition="$platform eq 'semitemu'"}selected{/if}>semitemu</option>
                </select>
            </div>
            <div class="layui-input-inline w200">
                <input type="text" class="layui-input" id="sale_day" name="sale_day" value="{$sale_day}" placeholder="请选择日期">
            </div>
            <div class="layui-inline">
                <button class="layui-btn" lay-submit lay-filter="Search"><i class="layui-icon">&#xe615;</i> 查询</button>
            </div>
        </form>
        <div class="layui-form pie-chart1">
            <div id="main_1" style="height:800px; width: 1600px"></div>
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
            elem: '#sale_day',
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

        const category_1 = echarts.init(document.getElementById("main_1"));
        category_1.setOption({
            title: {
                text:  '{$platform}平台销售柱状图',
                // subtext: 'Fake Data',
                left: 'center'
            },
            legend: {
                orient: 'vertical',
                left: 'right'
            },
            tooltip: {},
            xAxis: {
                type: 'category',
                data: [{$month}]
            },
            yAxis: {
                type: 'value'
            },
            series: [
                {
                    data: [{$amount}],
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
