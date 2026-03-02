
{include file="public/header" /}

<!-- 主体内容 -->
<div class="layui-body" id="LAY_app_body">
    <div class="right">
        <div class="title">{$group_name}产品列表</div>
        <div class="layui-form" style="overflow-x: auto;">
            <table class="layui-table" lay-size="sm">
                <colgroup>
                    <col>
                    <col>
                    <col>
                </colgroup>
                <thead>
                <tr>
                    <th>SKU</th>
                    <th>中文品名</th>
                    <th>主销售人员</th>
                </tr>
                </thead>
                <tbody>
                {foreach name="list" item="v"}
                <tr>
                    <td>{$v.productSku}</td>
                    <td>{$v.productTitle}</td>
                    <td>{$v.user_name}</td>
                </tr>
                {/foreach}
                </tbody>
            </table>
        </div>
    </div>
</div>
<script>
    layui.use(['form', 'jquery'], function(){
        let $ = layui.jquery,
            form = layui.form;

        $(function(){
            $('#LAY_app').addClass('layadmin-side-shrink');
        });

    });
</script>

{include file="public/footer" /}
