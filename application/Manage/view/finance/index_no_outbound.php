
{include file="public/header" /}

<style>
    .total {padding: 0 10px}
</style>
<!-- 主体内容 -->
<div class="layui-body" id="LAY_app_body">
    <div class="right">
        <a href="{:session('back_url', '', 'manage')}" class="layui-btn layui-btn-danger layui-btn-sm fr"><i class="layui-icon">&#xe603;</i>返回上一页</a>
        <div class="title">未出库列表</div>

        <div class="layui-form">
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
                    <col>
                    <col>
                </colgroup>
                <thead>
                <tr>
                    <th>平台</th>
                    <th>店铺</th>
                    <th>Payment</th>
                    <th>配送方式</th>
                    <th>销售SKU</th>
                    <th>仓库SKU</th>
                    <th>数量</th>
                    <th>账单销售</th>
                    <th>账单佣金</th>
                    <th>账单FBA尾程</th>
                    <th>出库销售</th>
                    <th>出库佣金</th>
                    <th>出库FBA尾程</th>
                </tr>
                </thead>
                <tbody>
                {foreach name="list" item="v"}
                <tr>
                    <td>{$v.platform}</td>
                    <td>{$v.userAccount}</td>
                    <td>{$v.payment_id}</td>
                    <td>{$v.fulfillment}</td>
                    <td>{$v.sku}</td>
                    <td>{$v.warehouse_sku}</td>
                    <td class="tr">{$v.quantity}</td>
                    <td class="tr">{$v.payment_amount}</td>
                    <td class="tr">{$v.payment_selling_fees}</td>
                    <td class="tr">{$v.payment_fba_fees}</td>
                    <td class="tr">{$v.outbound_amount}</td>
                    <td class="tr">{$v.outbound_selling_fee}</td>
                    <td class="tr">{$v.outbound_fba_fee}</td>
                </tr>
                {/foreach}
                </tbody>
            </table>
        </div>

    </div>
</div>
<script>
    layui.use(['form', 'jquery', 'upload', 'laydate'], function(){
        let $ = layui.jquery,
            form = layui.form,
            upload = layui.upload;

    });
</script>

{include file="public/footer" /}
