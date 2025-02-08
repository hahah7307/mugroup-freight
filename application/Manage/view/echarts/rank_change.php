
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
        <div class="title">{$listing.local_name}({$listing.asin})小类排名折线图</div>

        <form class="layui-form" method="get">
            <div class="layui-inline">
                <input type="hidden" class="layui-input" name="asin" value="{$asin}">
            </div>
            <div class="layui-inline w120">
                <select name="day" lay-verify="">
                    <option value="7" {if condition="$day eq 7"}selected{/if}>前一周</option>
                    <option value="14" {if condition="$day eq 14"}selected{/if}>前两周</option>
                    <option value="30" {if condition="$day eq 30"}selected{/if}>前一月</option>
                    <option value="90" {if condition="$day eq 90"}selected{/if}>前三月</option>
                    <option value="360" {if condition="$day eq 360"}selected{/if}>前一年</option>
                </select>
            </div>
            <div class="layui-inline">
                <button class="layui-btn" lay-submit lay-filter="Search"><i class="layui-icon">&#xe615;</i> 查询</button>
            </div>
        </form>

        <div class="product-img"><img src="{$listing.small_image_url}" alt="" height="100"></div>
        <div id="main" style="height:1000px;"></div>
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
                type: 'value',
                inverse: true
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
