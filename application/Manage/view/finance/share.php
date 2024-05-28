
{include file="public/header" /}

<!-- 主体内容 -->
<div class="layui-body" id="LAY_app_body">
    <div class="right">
        <a href="{:session('back_url', '', 'manage')}" class="layui-btn layui-btn-danger layui-btn-sm fr"><i class="layui-icon">&#xe603;</i>返回上一页</a>
        <div class="title">分摊列表</div>
        <form class="layui-form search-form" method="get">
            <div class="layui-inline w200">
                <input type="text" class="layui-input" name="keyword" value="{$keyword}" placeholder="payment/销售SKU/仓库SKU">
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
                </colgroup>
                <thead>
                <tr>
                    <th>发货方式</th>
                    <th>费用类型</th>
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
                    <td>{$v.fulfillment}</td>
                    <td>{$v.cost_type}</td>
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
