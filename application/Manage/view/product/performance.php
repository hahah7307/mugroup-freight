
{include file="public/header" /}

<!-- 主体内容 -->
<div class="layui-body" id="LAY_app_body">
    <div class="right">
        <div class="title">Listing排名表</div>
        <form class="layui-form" method="get">
            <div class="layui-input-inline w200">
                <input type="text" class="layui-input" id="start" name="date" value="{$keyword}" placeholder="日期">
            </div>
            <div class="layui-inline">
                <button class="layui-btn" lay-submit lay-filter="Search"><i class="layui-icon">&#xe615;</i> 查询</button>
            </div>
            <div class="layui-inline">
                <a href="{:url('expenses_export', ['date' => $keyword])}" class="layui-btn layui-btn-normal"><i class="layui-icon">&#xe63c;</i> 导出</a>
            </div>
        </form>

        <div class="layui-form table-flex">
            <table class="layui-table">
                <colgroup>
                    <col class="w80">
                    <col>
                    <col>
                    <col>
                    <col>
                    <col>
                    <col>
                    <col>
                    <col class="w120">
                </colgroup>
                <thead>
                <tr>
                    <th>ID</th>
                    <th>seller_sku</th>
                    <th>sid</th>
                    <th>asin</th>
                    <th>r_date</th>
                    <th>currency_code</th>
                    <th>product_name</th>
                    <th>map_value</th>
                    <th>created_date</th>
                </tr>
                </thead>
                <tbody>
                {foreach name="list" key="key" item="v"}
                <tr>
                    <td class="tr">{$v.id}</td>
                    <td>{$v.seller_sku}</td>
                    <td>{$v.sid}</td>
                    <td>{$v.asin}</td>
                    <td>{$v.r_date}</td>
                    <td>{$v.currency_code}</td>
                    <td>{$v.product_name}</td>
                    <td>{$v.map_value}</td>
                    <td>{$v.created_date}</td>
                </tr>
                {/foreach}
                </tbody>
            </table>
            {$list->render()}
        </div>

    </div>
</div>
<script>
    layui.use(['form', 'jquery', 'laydate'], function(){
        var $ = layui.jquery,
            form = layui.form,
            laydate = layui.laydate;

        // 显示日期选择器
        laydate.render({
            elem: '#start',
            type: 'date'
        });

        $(".sku-item").click(function(){
            let sku = $(this).data('asin');
            location.href = "/Manage/Echarts/rank_change/asin/" + sku + ".html";
        });
    });
</script>

{include file="public/footer" /}
