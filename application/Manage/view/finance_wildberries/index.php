
{include file="public/header" /}

<style>
    .total {padding: 0 10px}
</style>
<!-- 主体内容 -->
<div class="layui-body" id="LAY_app_body">
    <div class="right">
        <a href="{:url('report')}" class="layui-btn layui-btn-danger layui-btn-sm fr"><i class="layui-icon">&#xe603;</i>返回上一页</a>
        <div class="title">Wildberries费用列表</div>

        <div class="layui-form">
            <table class="layui-table" lay-size="sm">
                <colgroup>
                    <col width="120">
                    <col>
                    <col width="80">
                </colgroup>
                <thead>
                <tr>
                    <th>费用类</th>
                    <th>费用描述</th>
                    <th class="tc">操作</th>
                </tr>
                </thead>
                <tbody>
                <tr>
                    <td>归档订单</td>
                    <td></td>
                    <td class="tc">
                        <a href="{:url('wildberries')}" class="layui-btn layui-btn layui-btn-sm">查看</a>
                    </td>
                </tr>
                <tr>
                    <td>成本表</td>
                    <td></td>
                    <td class="tc">
                        <a href="{:url('cost')}" class="layui-btn layui-btn layui-btn-sm">查看</a>
                    </td>
                </tr>
                <tr>
                    <td>国内快递表</td>
                    <td></td>
                    <td class="tc">
                        <a href="{:url('express_delivery')}" class="layui-btn layui-btn layui-btn-sm">查看</a>
                    </td>
                </tr>
                </tbody>
            </table>
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
