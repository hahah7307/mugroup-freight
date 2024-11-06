
{include file="public/header" /}

<style>
    .total {padding: 0 10px}
</style>
<!-- 主体内容 -->
<div class="layui-body" id="LAY_app_body">
    <div class="right">
        <div class="title">营运费用</div>
        <form class="layui-form search-form" method="get">
            <div class="layui-input-inline w100">
                <select name="type">
                    <option value="1" {if condition="$type eq 1"}selected{/if}>已核算</option>
                    <option value="2" {if condition="$type eq 2"}selected{/if}>已支付</option>
                </select>
            </div>
            <div class="layui-input-inline">
                <input type="text" class="layui-input" id="month" name="month" value="{$month}" placeholder="核算月份">
            </div>
            <div class="layui-inline">
                <button class="layui-btn" lay-submit lay-filter="Search"><i class="layui-icon">&#xe615;</i> 查询</button>
            </div>
        </form>

        <a href="{:url('expenses')}" class="layui-btn">国内广告</a>
        <a href="{:url('factory')}" class="layui-btn">工厂运费</a>
        <a href="{:url('delivery')}" class="layui-btn">国际快递</a>
        <a href="{:url('factory_claim')}" class="layui-btn">工厂索赔</a><br><br>

        <span class="total">国内广告合计：{$list1_sum|number_format=###, 2}</span>
        <span class="total">工厂费用合计：{$list2_sum|number_format=###, 2}</span>
        <span class="total">国际快递合计：{$list3_sum|number_format=###, 2}</span>
        <span class="total">月核算合计：{$list1_sum + $list2_sum + $list3_sum|number_format=###, 2}</span>

        <table class="layui-table" lay-size="sm">
            <colgroup>
                <col>
                <col>
                <col>
                <col>
                <col>
                <col>
                <col>
                <col>
            </colgroup>
            <thead>
            <tr>
                <th>SKU</th>
                <th>申请人</th>
                <th>承担人</th>
                <th>支付月份</th>
                <th>支付币种</th>
                <th>支付金额</th>
                <th>备注</th>
                <th>费用类型</th>
                <th>核算月份</th>
                <th>种类</th>
            </tr>
            </thead>
            <tbody>
            {foreach name="list1" item="v1"}
            <tr>
                <td>{$v1.sku}</td>
                <td>{$v1.applicant}</td>
                <td></td>
                <td class="tr">{$v1.month}</td>
                <td>{$v1.currency}</td>
                <td class="tr">{$v1.total}</td>
                <td>{$v1.content}</td>
                <td>{$v1.type}</td>
                <td class="tr">{$v1.calculate_month}</td>
                <td>国内广告</td>
            </tr>
            {/foreach}
            {foreach name="list2" item="v2"}
            <tr>
                <td>{$v2.sku}</td>
                <td></td>
                <td></td>
                <td class="tr">{$v2.month}</td>
                <td>{$v2.currency}</td>
                <td class="tr">{$v2.total}</td>
                <td>{$v2.content}</td>
                <td>{$v2.type}</td>
                <td class="tr">{$v2.calculate_month}</td>
                <td>工厂运费</td>
            </tr>
            {/foreach}
            {foreach name="list3" item="v3"}
            <tr>
                <td>{$v3.sku}</td>
                <td>{$v3.sender}</td>
                <td>{$v3.seller}</td>
                <td class="tr">{$v3.month}</td>
                <td>CNY</td>
                <td class="tr">{$v3.total}</td>
                <td></td>
                <td></td>
                <td class="tr">{$v3.calculate_month}</td>
                <td>国际快递</td>
            </tr>
            {/foreach}
            </tbody>
        </table>
    </div>
</div>
<script>
    layui.use(['form', 'jquery', 'laydate'], function(){
        let $ = layui.jquery,
            form = layui.form,
            laydate = layui.laydate;

        //执行一个laydate实例
        laydate.render({
            elem: '#month' //指定元素
            ,type: 'month'
        });
    });
</script>

{include file="public/footer" /}
