
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
        <div class="title">各平台销量销售额排行榜</div>
        <form class="layui-form" method="get">
            平台：
            <div class="layui-inline w180">
                <input type="hidden" name="platform" id="categoryValue">
                <div id="selectTags"></div>
            </div>
            <div class="layui-input-inline w200">
                <input type="text" class="layui-input" id="start" name="start" value="{$start}" placeholder="开始时间">
            </div>
            <div class="layui-input-inline w200">
                <input type="text" class="layui-input" id="end" name="end" value="{$end}" placeholder="结束时间">
            </div>
            排序方式：
            <div class="layui-inline w100">
                <select name="order_field" lay-verify="">
                    <option value="qty" {if condition="$order_field eq 'qty'"}selected{/if}>销量</option>
                    <option value="amount" {if condition="$order_field eq 'amount'"}selected{/if}>销售额</option>
                </select>
            </div>
            <div class="layui-inline w120">
                <select name="order_type" lay-verify="">
                    <option value="DESC" {if condition="$order_type eq 'DESC'"}selected{/if}>从高到低</option>
                    <option value="ASC" {if condition="$order_type eq 'ASC'"}selected{/if}>从低到高</option>
                </select>
            </div>
            <span style="margin-left: 20px">
            <div class="layui-inline">
                <button class="layui-btn" lay-submit lay-filter="Search"><i class="layui-icon">&#xe615;</i> 查询</button>
            </div>
        </form>

        <div class="layui-form">
            <table class="layui-table" lay-size="sm">
                <colgroup>
                    <col class="w80">
                    <col class="w120">
                    <col>
                    <col>
                    <col>
                    <col>
                </colgroup>
                <thead>
                <tr>
                <tr>
                    <th>名次</th>
                    <th>仓库Sku</th>
                    <th>产品图片</th>
                    <th>销量</th>
                    <th>销售额</th>
                </tr>
                </thead>
                <tbody>
                {foreach name="saleList" key="key" item="v"}
                <tr class="sku-item" data-sku="{$v.warehouseSku}">
                    <td class="tr">{$key + 1}</td>
                    <td>{$v.warehouseSku}</td>
                    <td><a href="{$v.productImages}" target="_blank"><img src="{$v.productImages}" height="80" alt=""></a></td>
                    <td class="tr">{$v.qty|number_format=###}</td>
                    <td class="tr">{$v.amount|number_format=###, 2}</td>
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

        xmSelect.render({
            el: '#selectTags',
            on: function(data){
                document.getElementById('categoryValue').value = data.arr.map(v => v.value).join(',');
            },
            name: 'platform',
            toolbar: { show: true },
            filterable: true,
            tips: '请选择',
            data: [
                {name: 'amazon', value: 'amazon'},
                {name: 'wayfair', value: 'wayfairnew'},
                {name: 'walmart', value: 'walmart'},
                {name: 'shein', value: 'shein'},
                {name: 'ebay', value: 'ebay'},
                {name: 'shopify', value: 'shopify'},
                {name: 'tiktok', value: 'tiktok'}
            ],
            initValue: [{$platform}]
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

        $(".sku-item").click(function(){
            let sku = $(this).data('sku');
            location.href = "/Manage/SkuReport/wayfair_growth/sku/" + sku + "/platform/" + {$platform} + ".html";
        });
    });
</script>

{include file="public/footer" /}
