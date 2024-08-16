
{include file="public/header" /}

<!-- 主体内容 -->
<div class="layui-body" id="LAY_app_body">
    <div class="right">
        <a href="{:session('back_url', '', 'manage')}" class="layui-btn layui-btn-danger layui-btn-sm fr"><i class="layui-icon">&#xe603;</i>返回上一页</a>
        <div class="title">分摊列表</div>
        <form class="layui-form search-form" method="get">
            <div class="layui-inline w200">
                <input type="text" class="layui-input" name="keyword" value="{$keyword}" placeholder="">
            </div>
            <div class="layui-input-inline w180">
                <select name="cost_type">
                    <option value="">请选择费用类型</option>
                    <option value="REFUND" {if condition="$cost_type eq 'REFUND'"}selected{/if}>REFUND</option>
                    <option value="SHIPPING" {if condition="$cost_type eq 'SHIPPING'"}selected{/if}>SHIPPING</option>
                    <option value="ADJUSTMENT" {if condition="$cost_type eq 'ADJUSTMENT'"}selected{/if}>ADJUSTMENT</option>
                    <option value="LIQUIDATION" {if condition="$cost_type eq 'LIQUIDATION'"}selected{/if}>LIQUIDATION</option>
                    <option value="PROMOTION" {if condition="$cost_type eq 'PROMOTION'"}selected{/if}>PROMOTION</option>
                    <option value="WAREHOUSE_FBM" {if condition="$cost_type eq 'WAREHOUSE_FBM'"}selected{/if}>WAREHOUSE_FBM</option>
                    <option value="LEADJUSTMENT" {if condition="$cost_type eq 'LEADJUSTMENT'"}selected{/if}>LEADJUSTMENT</option>
                    <option value="LCADJUSTMENT" {if condition="$cost_type eq 'LCADJUSTMENT'"}selected{/if}>LCADJUSTMENT</option>
                    <option value="OPERATION_EXPENSES" {if condition="$cost_type eq 'OPERATION_EXPENSES'"}selected{/if}>OPERATION_EXPENSES</option>
                    <option value="OPERATION_FACTORY" {if condition="$cost_type eq 'OPERATION_FACTORY'"}selected{/if}>OPERATION_FACTORY</option>
                    <option value="OPERATION_EXPENSES_PATENT" {if condition="$cost_type eq 'OPERATION_EXPENSES_PATENT'"}selected{/if}>OPERATION_EXPENSES_PATENT</option>
                    <option value="OPERATION_DELIVERY" {if condition="$cost_type eq 'OPERATION_DELIVERY'"}selected{/if}>OPERATION_DELIVERY</option>
                    <option value="WFS_FULFILLMENT" {if condition="$cost_type eq 'WFS_FULFILLMENT'"}selected{/if}>WFS_FULFILLMENT</option>
                    <option value="WFS_RETURN_SHIPPING" {if condition="$cost_type eq 'WFS_RETURN_SHIPPING'"}selected{/if}>WFS_RETURN_SHIPPING</option>
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
                    <th>发货方式</th>
                    <th>费用类型</th>
                    <th>店铺名</th>
                    <th>payment</th>
                    <th>销售SKU</th>
                    <th>仓库SKU</th>
                    <th>总费用</th>
                    <th>占比</th>
                    <th>分摊费用</th>
                    <th>分摊标识</th>
                </tr>
                </thead>
                <tbody>
                {foreach name="list" item="v"}
                <tr>
                    <td class="tr">{$v.id}</td>
                    <td>{$v.fulfillment}</td>
                    <td>{$v.cost_type}</td>
                    <td>{$v.user_account}</td>
                    <td>{$v.payment}</td>
                    <td>{$v.seller_sku}</td>
                    <td>{$v.warehouse_sku}</td>
                    <td class="tr">{$v.amount}</td>
                    <td class="tr">{$v.percent}</td>
                    <td class="tr">{$v.total}</td>
                    <td>{$v.share_code}</td>
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
            form = layui.form;

    });
</script>

{include file="public/footer" /}
