
{include file="public/header" /}

<!-- 主体内容 -->
<div class="layui-body" id="LAY_app_body">
    <div class="right">
        <div class="title">Seller Sku列表</div>
        <form class="layui-form search-form" method="get">
            <div class="layui-inline w200">
                <input type="text" class="layui-input" name="keyword" value="{$keyword}" placeholder="Sku/店铺名">
            </div>
            <div class="layui-inline">
                <button class="layui-btn" lay-submit lay-filter="Search"><i class="layui-icon">&#xe615;</i> 查询</button>
            </div>
            <div class="layui-inline">
                <a class="layui-btn layui-btn-normal" href="{:url('index')}"><i class="layui-icon">&#xe621;</i> 重置</a>
            </div>
        </form>

        <div class="layui-form">
            <a class="layui-btn" href="{:url('add')}">添加</a>
            <button class="layui-btn layui-btn-danger ml0" lay-submit lay-filter="Update">更新</button>
            <table class="layui-table" lay-size="sm">
                <colgroup>
                    <col width="50">
                    <col>
                    <col>
                    <col>
                    <col width="80">
                    <col width="180">
                </colgroup>
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Seller Sku</th>
                    <th>店铺名</th>
                    <th>relation</th>
                    <th>仓库ID</th>
                    <th class="tc">操作</th>
                </tr>
                </thead>
                <tbody>
                {foreach name="list" item="v"}
                <tr>
                    <td>{$v.id}</td>
                    <td>{$v.product_sku}</td>
                    <td>{$v.user_account}</td>
                    <td>
                        {foreach name="$v.warehouse_sku" item="relation"}
                        &nbsp;{$relation.pcr_quantity} * {$relation.pcr_product_sku} ({$relation.pcr_percent}%)
                        {/foreach}
                    </td>
                    <td class="tr">{$v.warehouse_id}</td>
                    <td class="tc">
                        <a href="{:url('relation', ['id' => $v.id])}" class="layui-btn layui-btn-sm">查看</a>
                        <a href="{:url('edit', ['id' => $v.id])}" class="layui-btn layui-btn-normal layui-btn-sm">编辑</a>
                        <button data-id="{$v.id}" class="layui-btn layui-btn-sm layui-btn-danger ml0" lay-submit lay-filter="Detele">删除</button>
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
        var $ = layui.jquery,
            form = layui.form;

        // 删除
        form.on('submit(Detele)', function(data){
            var text = $(this).text(),
                button = $(this),
                id = $(this).data('id');
            layer.confirm('确定删除吗？',{icon:3,closeBtn:0,title:false,btnAlign:'c'},function(){
                $('button').attr('disabled',true);
                button.text('请稍候...');
                axios.post("{:url('delete')}", {id:id})
                    .then(function (response) {
                        var res = response.data;
                        if (res.code === 1) {
                            layer.alert(res.msg,{icon:1,closeBtn:0,title:false,btnAlign:'c',},function(){
                                location.reload();
                            });
                        } else {
                            layer.alert(res.msg,{icon:2,closeBtn:0,title:false,btnAlign:'c'},function(){
                                layer.closeAll();
                                $('button').attr('disabled',false);
                                button.text(text);
                            });
                        }
                    })
                    .catch(function (error) {
                        console.log(error);
                    });
                return false;
            });
        });

        // 更新
        form.on('submit(Update)', function(data){
            var text = $(this).text(),
                button = $(this);
            layer.confirm('确定更新sku映射吗？',{icon:3,closeBtn:0,title:false,btnAlign:'c'},function(){
                $('button').attr('disabled',true);
                button.text('请稍候...');
                axios.post("{:url('relation_update')}", {})
                    .then(function (response) {
                        var res = response.data;
                        if (res.code === 1) {
                            layer.alert(res.msg,{icon:1,closeBtn:0,title:false,btnAlign:'c',},function(){
                                location.reload();
                            });
                        } else {
                            layer.alert(res.msg,{icon:2,closeBtn:0,title:false,btnAlign:'c'},function(){
                                layer.closeAll();
                                $('button').attr('disabled',false);
                                button.text(text);
                            });
                        }
                    })
                    .catch(function (error) {
                        console.log(error);
                    });
                return false;
            });
        });
    });
</script>

{include file="public/footer" /}
