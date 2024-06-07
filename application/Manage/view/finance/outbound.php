
{include file="public/header" /}

<!-- 主体内容 -->
<div class="layui-body" id="LAY_app_body">
    <div class="right">
        <a href="{:session('back_url', '', 'manage')}" class="layui-btn layui-btn-danger layui-btn-sm fr"><i class="layui-icon">&#xe603;</i>返回上一页</a>
        <div class="title">出库明细列表</div>
        <form class="layui-form search-form" method="get">
            <div class="layui-inline w200">
                <input type="text" class="layui-input" name="keyword" value="{$keyword}" placeholder="参考/系统单号">
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
                    <col width="80">
                    <col>
                    <col>
                    <col>
                    <col>
                    <col>
                    <col>
                    <col>
                    <col width="140">
                    <col>
                    <col>
                    <col>
                    <col width="80">
                </colgroup>
                <thead>
                <tr>
                    <th>ID</th>
                    <th>参考单号</th>
                    <th>Payment</th>
                    <th>外销合同号</th>
                    <th>采购单号</th>
                    <th>店铺SKU</th>
                    <th>仓库SKU</th>
                    <th>发货方式</th>
                    <th>发货时间</th>
                    <th>数量</th>
                    <th>DDP</th>
                    <th>仓储费</th>
                    <th class="tc">核算DDP</th>
                </tr>
                </thead>
                <tbody>
                {foreach name="list" item="v"}
                <tr>
                    <td class="tr">{$v.id}</td>
                    <td>{$v.saleOrderCode}</td>
                    <td>{$v.payment_id}</td>
                    <td>{$v.store.export_no}</td>
                    <td>{$v.store.content}</td>
                    <td>{$v.seller_sku}</td>
                    <td>{$v.warehouse_sku}</td>
                    <td>{$v.fulfillment}</td>
                    <td>{$v.shipping_time}</td>
                    <td class="tr">{$v.qty}</td>
                    <td class="tr">{$v.store.sku_ddp_unit}</td>
                    <td class="tr">{$v.warehouse_rent}</td>
                    <td class="tc">
                        {if condition="$v.is_notify eq 0"}
                            <p class="blue">待核算</p>
                        {elseif condition="$v.is_notify eq 1" /}
                            <p class="green">已核算</p>
                        {elseif condition="$v.is_notify eq 2" /}
                            <p class="red">未通过</p>
                        {/if}
                    </td>
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
