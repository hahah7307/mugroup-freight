
{include file="public/header" /}

<!-- 主体内容 -->
<div class="layui-body" id="LAY_app_body">
    <div class="right">
        <div class="title">出库费</div>
        <form class="layui-form search-form" method="get">
            <div class="layui-inline w200">
                <select name="storage_id" lay-verify="">
                    <option value=""></option>
                    {foreach name="storage" item="va"}
                    <option value="{$va.id}" {if condition="$storage_id eq $va.id"}selected{/if}>{$va.name}</option>
                    {/foreach}
                </select>
            </div>
            <div class="layui-inline w200">
                <select name="platform_tag" lay-verify="">
                    <option value=""></option>
                    <option value="amazon" {if condition="$platform_tag eq 'amazon'"}selected{/if}>amazon</option>
                    <option value="wayfairnew" {if condition="$platform_tag eq 'wayfairnew'"}selected{/if}>wayfair</option>
                    <option value="walmart" {if condition="$platform_tag eq 'walmart'"}selected{/if}>walmart</option>
                </select>
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
            <table class="layui-table" lay-size="sm">
                <colgroup>
                    <col>
                    <col>
                    <col>
                    <col>
                    <col width="80">
                    <col width="80">
                    <col width="250">
                </colgroup>
                <thead>
                <tr>
                    <th class="tc">平台</th>
                    <th class="tc">名称</th>
                    <th class="tc">描述</th>
                    <th class="tc">金额</th>
                    <th class="tc">Level</th>
                    <th class="tc">状态</th>
                    <th class="tc">操作</th>
                </tr>
                </thead>
                <tbody>
                {foreach name="list" item="v"}
                <tr>
                    <td class="tc">{$v.platform_tag}</td>
                    <td class="tc">{$v.name}</td>
                    <td class="tc">{$v.description}</td>
                    <td class="tc">{$v.value}</td>
                    <td class="tc"><input type="text" name="level" lay-verify="required" class="layui-input w50" value="{$v.level}"></td>
                    <td class="tc">
                        <input type="checkbox" class="h30" name="look" value="{$v.id}" lay-skin="switch" lay-text="是|否" lay-filter="formLock" {if condition="$v.state eq 1"}checked{/if}>
                    </td>
                    <td class="tc">
                        <a href="{:url('edit', ['id' => $v.id])}" class="layui-btn layui-btn-normal layui-btn-sm">编辑</a>
                        <button data-id="{$v.id}" class="layui-btn layui-btn-sm layui-btn-danger ml0" lay-submit lay-filter="Delete">删除</button>
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

        // 状态
        form.on('switch(formLock)', function(data){
            $('button').attr('disabled',true);
            axios.post("{:url('status')}", {id:data.value,type:'look'})
                .then(function (response) {
                    let res = response.data;
                    if (res.code === 0) {
                        layer.alert(data.msg,{icon:2,closeBtn:0,title:false,btnAlign:'c'},function(){
                            location.reload();
                        });
                    }
                })
                .catch(function (error) {
                    console.log(error);
                });
            return false;
        });

        // 删除
        form.on('submit(Delete)', function(data){
            let text = $(this).text(),
                button = $(this),
                id = $(this).data('id');
            layer.confirm('确定删除吗？',{icon:3,closeBtn:0,title:false,btnAlign:'c'},function(){
                $('button').attr('disabled',true);
                button.text('请稍候...');
                axios.post("{:url('delete')}", {id:id})
                    .then(function (response) {
                        let res = response.data;
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
