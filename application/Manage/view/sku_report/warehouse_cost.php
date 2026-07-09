
{include file="public/header" /}

<!-- 主体内容 -->
<div class="layui-body" id="LAY_app_body">
    <div class="right">
        <a href="{:session('back_url', '', 'manage')}" class="layui-btn layui-btn-danger layui-btn-sm fr"><i class="layui-icon">&#xe603;</i>返回上一页</a>
        <div class="title">SKU月仓储费占海外仓账单比例</div>

        <div class="layui-form table-flex">
            <table class="layui-table" lay-size="sm">
                <colgroup>
                    <col width="80">
                    <col>
                    <col>
                    <col>
                    <col>
                </colgroup>
                <thead>
                <tr>
                    <th>核算ID</th>
                    <th>月份</th>
                    <th>仓储费合计（美金）</th>
                    <th>海外仓账单合计（美金）</th>
                    <th>占比（%）</th>
                </tr>
                </thead>
                <tbody>
                {foreach name="list" key="key" item="v"}
                <tr>
                    <td class="tr">{$v.id}</td>
                    <td>{$v.month}</td>
                    <td class="tr">{$v.total|number_format=###, 2}</td>
                    <td class="tr">{$v.paid_total|number_format=###, 2}</td>
                    <td class="tr">{$v.percent}</td>
                </tr>
                {/foreach}
                </tbody>
            </table>
        </div>

    </div>
</div>
<script>
    layui.use(['form', 'jquery'], function(){
        var $ = layui.jquery,
            form = layui.form;

    });
</script>

{include file="public/footer" /}
