
{include file="public/header" /}

<!-- 主体内容 -->
<div class="layui-body" id="LAY_app_body">
    <div class="right">
        <a href="{:session('back_url', '', 'manage')}" class="layui-btn layui-btn-danger layui-btn-sm fr"><i class="layui-icon">&#xe603;</i>返回上一页</a>
        <div class="title">本月已出库未核算订单列表</div>

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
                    <th>支付时间</th>
                    <th>订单状态</th>
                    <th>易仓自生成单号</th>
                    <th>跟踪号</th>
                    <th>发货批次号</th>
                    <th>Payment订单号</th>
                    <th>易仓发货推送号</th>
                    <th>平台sku</th>
                    <th>仓库sku</th>
                    <th>数量</th>
                    <th>单价</th>
                    <th>总额(加运费去coupon原币种)</th>
                    <th>运费</th>
                    <th>佣金</th>
                    <th>FBA尾程</th>
                    <th>税</th>
                </tr>
                </thead>
                <tbody>
                {foreach name="list" item="v"}
                <tr>
                    <td>{$v.platform}</td>
                    <td>{$v.user_account}</td>
                    <td>{$v.paid_time}</td>
                    <td>{$v.order_status}</td>
                    <td>{$v.warehouse_no}</td>
                    <td>{$v.shipping_no}</td>
                    <td>{$v.inventory_batch_no}</td>
                    <td>{$v.payment_id}</td>
                    <td>{$v.saleOrderCode}</td>
                    <td>{$v.platform_sku}</td>
                    <td>{$v.warehouse_sku}</td>
                    <td class="tr">{$v.qty}</td>
                    <td class="tr">{$v.sale_unit}</td>
                    <td class="tr">{$v.sale_amount}</td>
                    <td class="tr">{$v.sale_shipping}</td>
                    <td class="tr">{$v.selling_fee}</td>
                    <td class="tr">{$v.fba_fee}</td>
                    <td class="tr">{$v.tax}</td>
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

    });
</script>

{include file="public/footer" /}
