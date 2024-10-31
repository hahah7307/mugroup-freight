
{include file="public/header" /}

<!-- 主体内容 -->
<div class="layui-body" id="LAY_app_body">
    <div class="right">
        <a href="{:session('back_url', '', 'manage')}" class="layui-btn layui-btn-danger layui-btn-sm fr"><i class="layui-icon">&#xe603;</i>返回上一页</a>
        <div class="title">Payment列表</div>
        <form class="layui-form search-form" method="get">
            <div class="layui-inline w200">
                <input type="text" class="layui-input" name="keyword" value="{$keyword}" placeholder="原始单号">
            </div>
            <div class="layui-inline w120">
                <select name="order_type" lay-verify="">
                    <option value="">订单类型</option>
                    <option value="Order" {if condition="$order_type eq 'Order'"}selected{/if}>Order</option>
                    <option value="Service Fee" {if condition="$order_type eq 'Service Fee'"}selected{/if}>Service</option>
                    <option value="Refund" {if condition="$order_type eq 'Refund'"}selected{/if}>Refund</option>
                    <option value="Shipping Services" {if condition="$order_type eq 'Shipping Services'"}selected{/if}>Shipping</option>
                    <option value="FBA Inventory Fee" {if condition="$order_type eq 'FBA Inventory Fee'"}selected{/if}>FBA IF</option>
                    <option value="Adjustment" {if condition="$order_type eq 'Adjustment'"}selected{/if}>Adjustment</option>
                    <option value="FBA Customer Return Fee" {if condition="$order_type eq 'FBA Customer Return Fee'"}selected{/if}>FBA CRF</option>
                    <option value="Deal Fee" {if condition="$order_type eq 'Deal Fee'"}selected{/if}>Deal</option>
                    <option value="Liquidations" {if condition="$order_type eq 'Liquidations'"}selected{/if}>Liquidations</option>
                </select>
            </div>
            <div class="layui-inline w120">
                <select name="fulfillment" lay-verify="">
                    <option value="">发货类型</option>
                    <option value="Seller" {if condition="$fulfillment eq 'Seller'"}selected{/if}>Seller</option>
                    <option value="Amazon" {if condition="$fulfillment eq 'Amazon'"}selected{/if}>Amazon</option>
                </select>
            </div>
            <div class="layui-inline w100">
                <input type="text" class="layui-input" name="page_num" value="{$page_num}" placeholder="每页条数">
            </div>
            <div class="layui-inline">
                <button class="layui-btn" lay-submit lay-filter="Search"><i class="layui-icon">&#xe615;</i> 查询</button>
            </div>
        </form>

        <div class="layui-form">
            <table class="layui-table" lay-size="sm">
                <colgroup>
                    <col>
                    <col width="150">
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
                    <col>
                </colgroup>
                <thead>
                <tr>
                    <th>ID</th>
                    <th>payment单号</th>
                    <th>SKU</th>
                    <th>QTY</th>
                    <th>发货类型</th>
                    <th>产品</th>
                    <th>税</th>
                    <th>航运</th>
                    <th>税</th>
                    <th>包装</th>
                    <th>税</th>
                    <th>监管</th>
                    <th>税</th>
                    <th>促销</th>
                    <th>税</th>
                    <th>市场</th>
                    <th>销售</th>
                    <th>fba</th>
                    <th>其他交易费</th>
                    <th>其他</th>
                    <th>总计</th>
                </tr>
                </thead>
                <tbody>
                {foreach name="list" item="v"}
                <tr>
                    <td>{$v.id}</td>
                    <td>{$v.payment_id}</td>
                    <td>{$v.sku}</td>
                    <td class="tr">{$v.quantity}</td>
                    <td>{$v.fulfillment}</td>
                    <td class="tr">{$v.product_sales}</td>
                    <td class="tr">{$v.product_sales_tax}</td>
                    <td class="tr">{$v.shipping_credits}</td>
                    <td class="tr">{$v.shipping_credits_tax}</td>
                    <td class="tr">{$v.gift_wrap_credits}</td>
                    <td class="tr">{$v.gift_wrap_credits_tax}</td>
                    <td class="tr">{$v.regulatory_fee}</td>
                    <td class="tr">{$v.regulatory_fee_tax}</td>
                    <td class="tr">{$v.promotional_rebates}</td>
                    <td class="tr">{$v.promotional_rebates_tax}</td>
                    <td class="tr">{$v.marketplace_withheld_tax}</td>
                    <td class="tr">{$v.selling_fees}</td>
                    <td class="tr">{$v.fba_fees}</td>
                    <td class="tr">{$v.other_transaction_fees}</td>
                    <td class="tr">{$v.other}</td>
                    <td class="tr">{$v.total}</td>
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
