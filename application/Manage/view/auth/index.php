
{include file="public/header" /}

<!-- 主体内容 -->
<div class="layui-body" id="LAY_app_body">
    <div class="right">
        <div class="title">权限列表</div>

        <div class="layui-form">
            <table class="layui-table">
                <colgroup>
                    <col>
                    <col>
                    <col>
                </colgroup>
                <thead>
                <tr>
                    <th>管路员名称</th>
                    <th>模块</th>
                    <th>操作</th>
                </tr>
                </thead>
                <tbody>
                {foreach name="list" key="k" item="v"}
                    <?php $num = 0; ?>
                    {foreach name="v" key="kk" item="vv"}
                        {foreach name="vv" key="kkk" item="vvv"}
                        <tr>
                            {if condition="$num eq 0"}
                            <td rowspan="{:$count[$k]['num']}">{$k}</td>
                            {/if}
                            {if condition="$kkk eq 0"}
                            <td rowspan="{:$count[$k][$kk]}">{$kk}</td>
                            {/if}
                            <td>{$vvv}</td>
                        </tr>
                        <?php $num ++; ?>
                        {/foreach}
                    {/foreach}
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

    });
</script>

{include file="public/footer" /}
