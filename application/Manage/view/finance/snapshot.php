
{include file="public/header" /}

<style>
    .total {padding: 0 20px 0 0}
</style>
<!-- 主体内容 -->
<div class="layui-body" id="LAY_app_body">
    <div class="right">
        <div class="title">财报结存列表</div>
        <form class="layui-form search-form" method="get">
            <div class="layui-inline w200">
                <input type="text" class="layui-input" id="keyword" name="keyword" value="{$keyword}" placeholder="">
            </div>
            <div class="layui-inline w120">
                <select name="platform" lay-verify="" id="platform">
                    <option value="">平台类型</option>
                    <option value="amazon-FBM" {if condition="$platform eq 'amazon-FBM'"}selected{/if}>FBM</option>
                    <option value="amazon-FBA" {if condition="$platform eq 'amazon-FBA'"}selected{/if}>FBA</option>
                    <option value="wayfair" {if condition="$platform eq 'wayfair'"}selected{/if}>Wayfair</option>
                    <option value="walmart" {if condition="$platform eq 'walmart'"}selected{/if}>Walmart</option>
                    <option value="shein" {if condition="$platform eq 'shein'"}selected{/if}>Shein</option>
                    <option value="ebay" {if condition="$platform eq 'ebay'"}selected{/if}>Ebay</option>
                    <option value="temu" {if condition="$platform eq 'temu'"}selected{/if}>Temu</option>
                </select>
            </div>
            <div class="layui-input-inline">
                <input type="text" class="layui-input" id="month" name="month" value="{$month}">
            </div>
            <div class="layui-inline">
                <button class="layui-btn" lay-submit lay-filter="Search"><i class="layui-icon">&#xe615;</i> 查询</button>
            </div>
        </form>

        <div class="layui-form" style="overflow-x: auto;">
            <span class="total">销量合计：{$qty_amount|number_format=###}</span>
            <span class="total">销售合计：{$amount|number_format=###, 2}</span>
            <span class="total">毛利合计：{$profit|number_format=###, 2}</span>
            <table class="layui-table" lay-size="sm" id="table">
            </table>
        </div>
    </div>
</div>
<script>
    layui.use(['form', 'jquery', 'upload', 'laydate', 'table'], function(){
        let $ = layui.jquery,
            form = layui.form,
            laydate = layui.laydate,
            table = layui.table;

        // 显示日期选择器
        laydate.render({
            elem: '#month',
            type: 'month'
        });

        table.render({
            elem: '#table'
            ,url: '/Manage/Finance/getSnapshot'
            ,method: 'post'
            ,where: {
                platform: $('#platform').val(),
                month: $('#month').val(),
                keyword: $('#keyword').val()
            }
            ,height: 880
            ,limit: 20
            ,cols: [[
                {field:'month', title: '月份', width:90}
                ,{field:'platform', title: '平台', width:80}
                ,{field:'user_account', title: '店铺', width:180}
                ,{field:'warehouse_sku', title: 'SKU', width:120}
                ,{field:'product_name', title: '产品名称', width:200}
                ,{field:'seller', title: '运营', width:80}
                ,{field:'purchaser', title: '采购', width:80}
                ,{field:'sale_qty', title: '销售数量', width:100, align: 'right', templet: function(d) {
                    return Math.round(d.sale_qty);
                    }}
                ,{field:'refund_qty', title: '退款数量', width:100, align: 'right', templet: function(d) {
                        return Math.round(d.refund_qty);
                    }}
                ,{field:'qty_amount', title: '实际销量', width:100, align: 'right', templet: function(d) {
                        return Math.round(d.qty_amount);
                    }}
                ,{field:'sale_amount', title: '销售总额', width:100, align: 'right', templet: function(d) {
                        return Math.round(d.sale_amount * 100) / 100;
                    }}
                ,{field:'sale_tax', title: '税金', width:100, align: 'right', templet: function(d) {
                        return Math.round(d.sale_tax * 100) / 100;
                    }}
                ,{field:'refund_amount', title: '退款总额', width:100, align: 'right', templet: function(d) {
                        return Math.round(d.refund_amount * 100) / 100;
                    }}
                ,{field:'amount', title: '实际销售总额', width:120, align: 'right', templet: function(d) {
                        return Math.round(d.amount * 100) / 100;
                    }}
                ,{field:'sale_selling_fees', title: '平台佣金', width:100, align: 'right', templet: function(d) {
                        return Math.round(d.sale_selling_fees * 100) / 100;
                    }}
                ,{field:'refund_selling_fees', title: '平台佣金退回', width:120, align: 'right', templet: function(d) {
                        return Math.round(d.refund_selling_fees * 100) / 100;
                    }}
                ,{field:'fba_fees', title: 'FBA尾程', width:100, align: 'right', templet: function(d) {
                        return Math.round(d.fba_fees * 100) / 100;
                    }}
                ,{field:'fba_refund_fees', title: 'FBA尾程退款', width:120, align: 'right', templet: function(d) {
                        return Math.round(d.fba_refund_fees * 100) / 100;
                    }}
                ,{field:'refund_other', title: '退款其他', width:100, align: 'right', templet: function(d) {
                        return Math.round(d.refund_other * 100) / 100;
                    }}
                ,{field:'calcuRes', title: 'FBM尾程', width:100, align: 'right', templet: function(d) {
                        return Math.round(d.calcuRes * 100) / 100;
                    }}
                ,{field:'wfs_fulfillment', title: 'WFS尾程', width:100, align: 'right', templet: function(d) {
                        return Math.round(d.wfs_fulfillment * 100) / 100;
                    }}
                ,{field:'ddp', title: 'DDP', width:100, align: 'right', templet: function(d) {
                        return Math.round(d.ddp * 100) / 100;
                    }}
                ,{field:'adCost', title: '平台广告费', width:100, align: 'right', templet: function(d) {
                        return Math.round(d.adCost * 100) / 100;
                    }}
                ,{field:'warehouse_rent', title: '仓储费', width:100, align: 'right', templet: function(d) {
                        return Math.round(d.warehouse_rent * 100) / 100;
                    }}
                ,{field:'wfs_warehouse', title: 'WFS仓储费', width:120, align: 'right', templet: function(d) {
                        return Math.round(d.wfs_warehouse * 100) / 100;
                    }}
                ,{field:'wfs_return_shipping', title: 'WFS退运费', width:120, align: 'right', templet: function(d) {
                        return Math.round(d.wfs_return_shipping * 100) / 100;
                    }}
                ,{field:'adjustment', title: '调整费用', width:100, align: 'right', templet: function(d) {
                        return Math.round(d.adjustment * 100) / 100;
                    }}
                ,{field:'liquidation', title: '清算费用', width:100, align: 'right', templet: function(d) {
                        return Math.round(d.liquidation * 100) / 100;
                    }}
                ,{field:'promotion', title: '促销费用', width:100, align: 'right', templet: function(d) {
                        return Math.round(d.promotion * 100) / 100;
                    }}
                ,{field:'shipping_service', title: '退运费用', width:100, align: 'right', templet: function(d) {
                        return Math.round(d.shipping_service * 100) / 100;
                    }}
                ,{field:'lc_adjustment', title: '良仓调整', width:100, align: 'right', templet: function(d) {
                        return Math.round(d.lc_adjustment * 100) / 100;
                    }}
                ,{field:'le_adjustment', title: '乐歌调整', width:100, align: 'right', templet: function(d) {
                        return Math.round(d.le_adjustment * 100) / 100;
                    }}
                ,{field:'wfs_adjustment', title: 'WFS调整', width:100, align: 'right', templet: function(d) {
                        return Math.round(d.wfs_adjustment * 100) / 100;
                    }}
                ,{field:'operation_expenses', title: '国内广告费', width:100, align: 'right', templet: function(d) {
                        return Math.round(d.operation_expenses * 100) / 100;
                    }}
                ,{field:'operation_factory', title: '工厂运费', width:100, align: 'right', templet: function(d) {
                        return Math.round(d.operation_factory * 100) / 100;
                    }}
                ,{field:'operation_delivery', title: '国内快递费', width:100, align: 'right', templet: function(d) {
                        return Math.round(d.operation_delivery * 100) / 100;
                    }}
                ,{field:'ad_percent', title: '广告占比', width:100, align: 'right', templet: function(d) {
                        return Math.round(d.ad_percent * 100) / 100;
                    }}
                ,{field:'warehouse_percent', title: '仓储占比', width:100, align: 'right', templet: function(d) {
                        return Math.round(d.warehouse_percent * 100) / 100;
                    }}
                ,{field:'tail_percent', title: '尾程占比', width:100, align: 'right', templet: function(d) {
                        return Math.round(d.tail_percent * 100) / 100;
                    }}
                ,{field:'ddp_percent', title: 'DDP占比', width:100, align: 'right', templet: function(d) {
                        return Math.round(d.ddp_percent * 100) / 100;
                    }}
                ,{field:'profit', title: '毛利', width:100, align: 'right', templet: function(d) {
                        return Math.round(d.profit * 100) / 100;
                    }}
                ,{field:'gross_profit_margin', title: '毛利率', width:100, align: 'right', templet: function(d) {
                        return Math.round(d.gross_profit_margin * 100) / 100;
                    }}
                ,{field:'evaluation_qty', title: '测评数量', width:100, align: 'right', templet: function(d) {
                        return Math.round(d.evaluation_qty);
                    }}
                ,{field:'evaluation_amount', title: '测评总额', width:100, align: 'right', templet: function(d) {
                        return Math.round(d.evaluation_amount * 100) / 100;
                    }}
                ,{field:'profit_include_evaluation', title: '含测评毛利', width:100, align: 'right', templet: function(d) {
                        return Math.round(d.profit_include_evaluation * 100) / 100;
                    }}
                ,{field:'gross_profit_margin_include_evaluation', title: '含测评毛利率', width:120, align: 'right', templet: function(d) {
                        return Math.round(d.gross_profit_margin_include_evaluation * 100) / 100;
                    }}
            ]]
            ,page: true //开启分页
            //更多设置...
        });
    });
</script>

{include file="public/footer" /}
