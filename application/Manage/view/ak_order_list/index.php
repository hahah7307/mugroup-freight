
{include file="public/header" /}

<!-- 主体内容 -->
<div class="layui-body" id="LAY_app_body">
    <div class="right">
        <div class="title">Temu订单</div>
        <form class="layui-form search-form" method="get">
            <div class="layui-inline w200">
                <input type="text" class="layui-input" name="keyword" value="{$keyword}" placeholder="">
            </div>
            <div class="layui-inline w100">
                <input type="text" class="layui-input" name="page_num" value="{$page_num}" placeholder="每页条数">
            </div>
            <div class="layui-input-inline w120">
                <input type="text" class="layui-input" id="start" name="start" value="{$start}" placeholder="开始日期">
            </div>
            <div class="layui-input-inline w120">
                <input type="text" class="layui-input" id="end" name="end" value="{$end}" placeholder="结束日期">
            </div>
            <div class="layui-inline">
                <button class="layui-btn" lay-submit lay-filter="Search"><i class="layui-icon">&#xe615;</i> 查询</button>
            </div>
            <div class="layui-inline">
                <a class="layui-btn layui-btn-normal" href="{:url('index')}"><i class="layui-icon">&#xe669;</i> 重置</a>
            </div>
        </form>

        <div class="layui-form">
<!--            <button type="button" class="layui-btn  layui-btn-disabled" id="excel">导入</button>-->
            <span class="total">合计：{$sum}</span>
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
                    <col>
                    <col>
                    <col>
                </colgroup>
                <thead>
                <tr>
                    <th>ID</th>
                    <th>global_order_no</th>
                    <th>store_name</th>
                    <th>platform_order_no</th>
                    <th>status</th>
                    <th>global_payment_time</th>
                    <th>amount_currency</th>
                    <th>unit_price_amount</th>
                    <th>item_price_amount</th>
                    <th>quantity</th>
                    <th>created_time</th>
                </tr>
                </thead>
                <tbody>
                {foreach name="list" item="v"}
                <tr>
                    <td>{$v.id}</td>
                    <td>{$v.global_order_no}</td>
                    <td>{$v.store_name}</td>
                    <td>{$v.platform_order_no}</td>
                    <td>{$v.status}</td>
                    <td>{$v.global_payment_time}</td>
                    <td>{$v.amount_currency}</td>
                    <td>{$v.unit_price_amount}</td>
                    <td>{$v.item_price_amount}</td>
                    <td>{$v.quantity}</td>
                    <td>{$v.created_time}</td>
                </tr>
                {/foreach}
                </tbody>
            </table>
            {$list->render()}
        </div>

    </div>
</div>
<script>
    layui.use(['form', 'jquery', 'upload', 'laydate'], function(){
        let $ = layui.jquery,
            form = layui.form,
            upload = layui.upload,
            laydate = layui.laydate;


        // 显示日期选择器
        laydate.render({
            elem: '#start',
            type: 'date'
        });
        laydate.render({
            elem: '#end',
            type: 'date'
        });
    });
</script>

{include file="public/footer" /}
