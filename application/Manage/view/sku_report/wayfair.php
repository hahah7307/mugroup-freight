
{include file="public/header" /}

<style>
    .layui-body {left: 220px!important;}
    .layui-form-label {width: 100px!important;}
    .layui-form-item .layui-inline {margin-right: 0!important;}
    .layui-form-label {width: 160px!important;}
    .w84 {width: 84px!important;}
    .deliver_num {width: 100px!important;}
    .table-flex {display: flex}
    /*.layui-table {display: flex}*/
    .select {margin-left: 0!important;}
    .warm-tips {display: inline-block; font-size: 14px; position: relative; top: 8px; left: 5px; color: #ce0000}
    .pie-chart {margin-top: 32px}
</style>
<!-- 主体内容 -->
<script src="/static/echarts/dist/echarts.min.js"></script>
<script src="/static/echarts/test/lib/jquery.min.js"></script>
<div class="layui-body" id="LAY_app_body">
    <div class="right">
        <div class="title">Wayfair环比销量增长率排行榜<span class="red">(销量有增长但增长率为0%表示上月无销量，销量增长为负且增长率为-100%表示本月无销量，上上月和上周同理)</span></div>
        <form class="layui-form" method="get">
            <div class="layui-input-inline w200">
                <input type="text" class="layui-input" id="start" name="start" value="{$start}" placeholder="开始时间">
            </div>
            <div class="layui-input-inline w200">
                <input type="text" class="layui-input" id="end" name="end" value="{$end}" placeholder="结束时间">
            </div>
            环比上月排序：
            <div class="layui-inline w100">
                <select name="last_diff" lay-verify="">
                    <option value="diff_rate" {if condition="$last_diff eq 'diff_rate'"}selected{/if}>增长率</option>
                    <option value="diff" {if condition="$last_diff eq 'diff'"}selected{/if}>增长量</option>
                </select>
            </div>
            <div class="layui-inline w120">
                <select name="last_order" lay-verify="">
                    <option value="DESC" {if condition="$last_order eq 'DESC'"}selected{/if}>从高到低</option>
                    <option value="ASC" {if condition="$last_order eq 'ASC'"}selected{/if}>从低到高</option>
                </select>
            </div>
            <span style="margin-left: 20px">
            环比上上月排序：</span>
            <div class="layui-inline w100">
                <select name="last2_diff" lay-verify="">
                    <option value="diff_rate" {if condition="$last2_diff eq 'diff_rate'"}selected{/if}>增长率</option>
                    <option value="diff" {if condition="$last2_diff eq 'diff'"}selected{/if}>增长量</option>
                </select>
            </div>
            <div class="layui-inline w120">
                <select name="last2_order" lay-verify="">
                    <option value="DESC" {if condition="$last2_order eq 'DESC'"}selected{/if}>从高到低</option>
                    <option value="ASC" {if condition="$last2_order eq 'ASC'"}selected{/if}>从低到高</option>
                </select>
            </div>
            <span style="margin-left: 400px">
            环比上周排序：</span>
            <div class="layui-inline w100">
                <select name="week_diff" lay-verify="">
                    <option value="diff_rate" {if condition="$week_diff eq 'diff_rate'"}selected{/if}>增长率</option>
                    <option value="diff" {if condition="$week_diff eq 'diff'"}selected{/if}>增长量</option>
                </select>
            </div>
            <div class="layui-inline w120">
                <select name="week_order" lay-verify="">
                    <option value="DESC" {if condition="$week_order eq 'DESC'"}selected{/if}>从高到低</option>
                    <option value="ASC" {if condition="$week_order eq 'ASC'"}selected{/if}>从低到高</option>
                </select>
            </div>
            <div class="layui-inline">
                <button class="layui-btn" lay-submit lay-filter="Search"><i class="layui-icon">&#xe615;</i> 查询</button>
            </div>
        </form>

        <div class="layui-form pie-chart" style="display: flex">
            <div id="main_1" style="height:500px; width: 800px"></div>
            <div id="main_2" style="height:500px; width: 800px"></div>
        </div>

        <div class="layui-form table-flex">
            <table class="layui-table" lay-size="sm">
                <colgroup>
                    <col width="50">
                    <col>
                    <col>
                    <col>
                    <col>
                    <col>
                    <col>
                </colgroup>
                <thead>
                <tr>
                    <th class="tc" colspan="7">环比<strong>上月</strong>销量增长率排行榜</th>
                </tr>
                <tr>
                    <th>名次</th>
                    <th>仓库Sku</th>
                    <th>产品图片</th>
                    <th>本月</th>
                    <th>上月</th>
                    <th>环比上月增长销量</th>
                    <th>环比上月增长率</th>
                </tr>
                </thead>
                <tbody>
                {foreach name="saleList" key="key" item="v"}
                <tr class="sku-item" data-sku="{$v.warehouseSku}" {if condition="$v.diff lt 0"}style="background: #f8b9b7"{/if}>
                    <td class="tr">{$key + 1}</td>
                    <td>{$v.warehouseSku}</td>
                    <td><img src="{$v.productImages}" height="80" alt=""></td>
                    <td class="tr">{$v.current|number_format=###}</td>
                    <td class="tr">{$v.last|number_format=###}</td>
                    <td class="tr">{$v.diff|number_format=###}</td>
                    <td class="tr">{$v.diff_rate * 100|number_format=###,2}%</td>
                </tr>
                {/foreach}
                </tbody>
            </table>
            <table class="layui-table" lay-size="sm">
                <colgroup>
                    <col width="50">
                    <col
                    <col>
                    <col>
                    <col>
                    <col>
                    <col>
                </colgroup>
                <thead>
                <tr>
                    <th class="tc" colspan="7">环比<strong>上上月</strong>销量增长率排行榜</th>
                </tr>
                <tr>
                    <th>名次</th>
                    <th>仓库Sku</th>
                    <th>产品图片</th>
                    <th>本月</th>
                    <th>上上月</th>
                    <th>环比上上月增长销量</th>
                    <th>环比上上月增长率</th>
                </tr>
                </thead>
                <tbody>
                {foreach name="sale2List" key="k" item="item"}
                <tr class="sku-item" data-sku="{$item.warehouseSku}" {if condition="$item.diff lt 0"}style="background: #f8b9b7"{/if}>
                    <td class="tr">{$k + 1}</td>
                    <td>{$item.warehouseSku}</td>
                    <td><img src="{$item.productImages}" height="80" alt=""></td>
                    <td class="tr">{$item.current|number_format=###}</td>
                    <td class="tr">{$item.last|number_format=###}</td>
                    <td class="tr">{$item.diff|number_format=###}</td>
                    <td class="tr">{$item.diff_rate * 100|number_format=###,2}%</td>
                </tr>
                {/foreach}
                </tbody>
            </table>
            <table class="layui-table" lay-size="sm">
                <colgroup>
                    <col width="50">
                    <col
                    <col>
                    <col>
                    <col>
                    <col>
                    <col>
                </colgroup>
                <thead>
                <tr>
                    <th class="tc" colspan="7">环比<strong>上周</strong>销量增长率排行榜</th>
                </tr>
                <tr>
                    <th>名次</th>
                    <th>仓库Sku</th>
                    <th>产品图片</th>
                    <th>本周</th>
                    <th>上周</th>
                    <th>环比上周增长销量</th>
                    <th>环比上周增长率</th>
                </tr>
                </thead>
                <tbody>
                {foreach name="weekList" key="wk" item="wv"}
                <tr class="sku-item" data-sku="{$wv.warehouseSku}" {if condition="$wv.diff lt 0"}style="background: #f8b9b7"{/if}>
                    <td class="tr">{$wk + 1}</td>
                    <td>{$wv.warehouseSku}</td>
                    <td><img src="{$wv.productImages}" height="80" alt=""></td>
                    <td class="tr">{$wv.current|number_format=###}</td>
                    <td class="tr">{$wv.last|number_format=###}</td>
                    <td class="tr">{$wv.diff|number_format=###}</td>
                    <td class="tr">{$wv.diff_rate * 100|number_format=###,2}%</td>
                </tr>
                {/foreach}
                </tbody>
            </table>
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
                text: 'Sku个数增减饼状图',
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
            color: ['#FF6347', '#0000CD', '#90EE90'],
            series: [
                {
                    type: 'pie',
                    data: {$is_growth},
                    label: {
                        normal: {
                            show: true,
                            position: 'inner', // 数值显示在内部
                            formatter: function (c) {
                                return moneyFormat(c.value, 0);
                            }
                        },
                    },
                    emphasis: {
                        itemStyle: {
                            shadowBlur: 10,
                            shadowOffsetX: 0,
                            shadowColor: 'rgba(0, 0, 0, 0.5)'
                        }
                    }
                }
            ]
        });

        const category_2 = echarts.init(document.getElementById("main_2"));
        category_2.setOption({
            title: {
                text: '销售数量增减饼状图',
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
            color: ['#FF6347', '#0000CD'],
            series: [
                {
                    type: 'pie',
                    data: {$growth_num},
                    label: {
                        normal: {
                            show: true,
                            position: 'inner', // 数值显示在内部
                            formatter: function (c) {
                                return moneyFormat(c.value, 0);
                            }
                        },
                    },
                    emphasis: {
                        itemStyle: {
                            shadowBlur: 10,
                            shadowOffsetX: 0,
                            shadowColor: 'rgba(0, 0, 0, 0.5)'
                        }
                    }
                }
            ]
        });

        $(".sku-item").click(function(){
            let sku = $(this).data('sku');
            location.href = "/Manage/SkuReport/wayfair_growth/sku/" + sku + ".html";
        });
    });
</script>

{include file="public/footer" /}
