
{include file="public/header" /}

<style>
    .layui-btn {margin: 4px 0}
</style>

<!-- 主体内容 -->
<div class="layui-body" id="LAY_app_body">
    <div class="right">
        <div class="title">财务报表</div>
        <form class="layui-form search-form" method="get">
            <div class="layui-inline w200">
                <input type="text" class="layui-input" name="keyword" value="{$keyword}" placeholder="报表名称">
            </div>
            <div class="layui-inline">
                <button class="layui-btn" lay-submit lay-filter="Search"><i class="layui-icon">&#xe615;</i> 查询</button>
            </div>
            <div class="layui-inline">
                <a class="layui-btn layui-btn-normal" href="{:url('index')}"><i class="layui-icon">&#xe621;</i> 重置</a>
            </div>
        </form>

        <div class="layui-form">
            <a class="layui-btn" href="{:url('report_add')}">添加</a>
            <button class="layui-btn layui-btn-danger" id="introduction">使用说明</button>
            <table class="layui-table">
                <colgroup>
                    <col width="50">
                    <col>
                    <col width="100">
                    <col width="80">
                    <col width="220">
                </colgroup>
                <thead>
                <tr>
                    <th>ID</th>
                    <th>报表名称</th>
                    <th>月份</th>
                    <th class="tc">状态</th>
                    <th class="tc">操作</th>
                </tr>
                </thead>
                <tbody>
                {foreach name="list" item="v"}
                <tr>
                    <td>{$v.id}</td>
                    <td>{$v.name}</td>
                    <td>{$v.month}</td>
                    <td class="tc">
                        {if condition="$v.is_notify eq 1"}
                            <span class="green">已同步</span>
                        {else/}
                            <span class="red">未同步</span>
                        {/if}
                    </td>
                    <td class="tl">
                        <a href="{:url('index', ['id' => $v.id])}" class="layui-btn layui-btn-sm">详情</a>
                        <a href="{:url('store', ['id' => $v.id])}" class="layui-btn layui-btn-sm">库存</a>
                        <a href="{:url('warehouse', ['id' => $v.id])}" class="layui-btn layui-btn-sm">仓租</a>
                        <a href="{:url('additional', ['id' => $v.id])}" class="layui-btn layui-btn-sm">额外</a>
                        <a href="{:url('evaluation', ['id' => $v.id])}" class="layui-btn layui-btn-sm">测评</a>
                        <a href="{:url('outbound', ['id' => $v.id])}" class="layui-btn layui-btn-sm">出库</a>
                        <a href="{:url('report_edit', ['id' => $v.id])}" class="layui-btn layui-btn-normal layui-btn-sm">编辑</a>
                        <a href="{:url('report_export', ['id' => $v.id, 'month' => $v.month])}" class="layui-btn layui-btn-normal layui-btn-sm">导出</a>
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

        // 使用说明
        $("#introduction").click(function() {
            layer.alert('见到你真的很高兴', {
                title: false,
                content: "使用说明：<br>" +
                    "按顺序进行以下操作方可导出<br>" +
                    "①导入当月所有payment订单<br>" +
                    "②导入月初批次库存和对应DDP<br>" +
                    "③导入当月海外仓账单仓租费用<br>" +
                    "④导入运营分摊四项调整费用<br>" +
                    "⑤导入测评订单<br>" +
                    "⑥等待报表状态变为已同步",
                btnAlign: 'c',
                closeBtn: 0,
                anim: 1,
            });
        });

        // 状态
        form.on('switch(formLock)', function(data){
            $('button').attr('disabled',true);
            axios.post("{:url('status')}", {id:data.value,type:'look'})
                .then(function (response) {
                    var res = response.data;
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
    });
</script>

{include file="public/footer" /}
