
{include file="public/header" /}

<style>
    .total {padding: 0 10px}
</style>
<!-- 主体内容 -->
<div class="layui-body" id="LAY_app_body">
    <div class="right">
        <a href="{:url('cost', ['id' => $report_id])}" class="layui-btn layui-btn-danger layui-btn-sm fr"><i class="layui-icon">&#xe603;</i>返回上一页</a>
        <div class="title">其他费用列表</div>
        <form class="layui-form search-form" method="get">
            <div class="layui-inline w200">
                <input type="text" class="layui-input" name="keyword" value="{$keyword}" placeholder="">
            </div>
            <div class="layui-inline w100">
                <input type="text" class="layui-input" name="page_num" value="{$page_num}" placeholder="每页条数">
            </div>
            <div class="layui-inline">
                <button class="layui-btn" lay-submit lay-filter="Search"><i class="layui-icon">&#xe615;</i> 查询</button>
            </div>
        </form>

        <div class="layui-form">
            <span class="total">广告费合计：{$ad_sum|number_format=###,2}</span>
            <span class="total">促销费合计：{$promotion|number_format=###,2}</span>
            <span class="total">清算费合计：{$liquidation_1 + $liquidation_2|number_format=###,2}</span>
            <span class="total">FBA仓储费合计：{$warehouse_sum|number_format=###,2}</span>
            <table class="layui-table" lay-size="sm">
                <colgroup>
                    <col width="80">
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
                    <th>月份</th>
                    <th>MSKU</th>
                    <th>广告费</th>
                    <th>标签费</th>
                    <th>月度仓储费差异</th>
                    <th>月度仓库费</th>
                    <th>长期仓储费</th>
                    <th>FBA销毁费</th>
                    <th>合作承运费</th>
                    <th>合仓费</th>
                    <th>计划外服务费</th>
                    <th>清算收入</th>
                    <th>清算费</th>
                    <th>VAT/GST</th>
                    <th>秒杀费</th>
                    <th>优惠卷</th>
                    <th>vine</th>
                    <th>国别</th>
                </tr>
                </thead>
                <tbody>
                {foreach name="list" item="v"}
                <tr>
                    <td class="tr">{$v.id}</td>
                    <td>{$v.reportDateMonth}</td>
                    <td>{$v.msku}</td>
                    <td>{$v.totalAdsCost}</td>
                    <td>{$v.sharedLabelingFee}</td>
                    <td>{$v.sharedFbaStorageFee}</td>
                    <td>{$v.fbaStorageFee}</td>
                    <td>{$v.longTermStorageFee}</td>
                    <td>{$v.sharedFbaDisposalFee}</td>
                    <td>{$v.sharedAmazonPartneredCarrierShipmentFee}</td>
                    <td>{$v.sharedFbaInboundConvenienceFee}</td>
                    <td>{$v.sharedFbaInboundDefectFee}</td>
                    <td>{$v.fbaLiquidationProceeds}</td>
                    <td>{$v.sharedLiquidationsFees}</td>
                    <td>{$v.taxCollected}</td>
                    <td>{$v.sharedLdFee}</td>
                    <td>{$v.sharedCouponFee}</td>
                    <td>{$v.sharedVineFee}</td>
                    <td>{$v.countryCode}</td>
                </tr>
                {/foreach}
                </tbody>
            </table>
            {$list->render()}
        </div>

    </div>
</div>
<script>
    layui.use(['form', 'jquery'], function(){
        let $ = layui.jquery,
            form = layui.form;

    });
</script>

{include file="public/footer" /}
