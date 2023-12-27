
{include file="public/header" /}

<style>
    .calcuRes {cursor: pointer;}
</style>
<!-- 主体内容 -->
<div class="layui-body" id="LAY_app_body">
    <div class="right">
        <div class="title">订单列表</div>
        <form class="layui-form search-form" method="get">
            <div class="layui-inline w200">
                <input type="text" class="layui-input" name="keyword" value="{$keyword}" placeholder="参考/销售/系统单号">
            </div>
            <div class="layui-inline w100">
                <input type="text" class="layui-input" name="page_num" value="{$page_num}" placeholder="每页条数">
            </div>
            <div class="layui-inline">
                <button class="layui-btn" lay-submit lay-filter="Search"><i class="layui-icon">&#xe615;</i> 查询</button>
            </div>
            <div class="layui-inline">
                <a class="layui-btn layui-btn-normal" href="{:url('index')}"><i class="layui-icon">&#xe621;</i> 重置</a>
            </div>
        </form>

        <div class="layui-form">
<!--            <a class="layui-btn" href="{:url('add')}">添加</a>-->
            <a class="layui-btn layui-btn-normal" lay-submit lay-filter="Calculate">测算</a>
            <table class="layui-table">
                <colgroup>
                    <col width="50">
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
                    <col width="80">
                    <col width="250">
                </colgroup>
                <thead>
                <tr>
                    <th class="tc">
                        <input type="checkbox" lay-skin="primary" id="YanNanQiu_checkall" lay-filter="YanNanQiu_checkall">
                    </th>
                    <th>ID</th>
                    <th>参考单号</th>
                    <th>销售单号</th>
                    <th>系统单号</th>
                    <th>平台代码</th>
                    <th>订单类型</th>
                    <th>总金额</th>
                    <th>跟踪号</th>
                    <th>创建时间</th>
                    <th>运费</th>
                    <th class="tc">状态</th>
                    <th class="tc">操作</th>
                </tr>
                </thead>
                <tbody>
                {foreach name="list" item="v"}
                <tr>
                    <td class="tc">
                        <div class="YanNanQiu_Checkbox">
                            <input type="checkbox" name="id[]" lay-skin="primary" lay-filter="imgbox" class="YanNanQiu_imgId" value="{$v.id}">
                        </div>
                    </td>
                    <td>{$v.id}</td>
                    <td>{$v.refNo}</td>
                    <td>{$v.saleOrderCode}</td>
                    <td>{$v.sysOrderCode}</td>
                    <td>{$v.platform}</td>
                    <td>{$v.orderType}</td>
                    <td>{$v.amountpaid}</td>
                    <td>{$v.shippingMethodNo}</td>
                    <td>{$v.createdDate}</td>
                    <td class="calcuRes" data-info="{$v.calcuInfo}">{$v.calcuRes}</td>
                    <td class="tc">
                        {if condition="$v.status eq 0"}
                            <p>已废弃</p>
                        {elseif condition="$v.status eq 1" /}
                            <p>未付款</p>
                        {elseif condition="$v.status eq 2" /}
                            <p>待审核</p>
                        {elseif condition="$v.status eq 3" /}
                            <p>待发货</p>
                        {elseif condition="$v.status eq 4" /}
                            <p>已发货</p>
                        {elseif condition="$v.status eq 5" /}
                            <p>冻结中</p>
                        {elseif condition="$v.status eq 6" /}
                            <p>缺货</p>
                        {elseif condition="$v.status eq 7" /}
                            <p>问题件</p>
                        {elseif condition="$v.status eq 8" /}
                            <p>未付款</p>
                        {/if}
                    </td>
                    <td class="tc">
<!--                        <a href="{:url('deliver/index', ['id' => $v.id])}" class="layui-btn layui-btn-sm">运送</a>-->
<!--                        <a href="{:url('pick/index', ['id' => $v.id])}" class="layui-btn layui-btn-sm">出库</a>-->
<!--                        <a href="{:url('edit', ['id' => $v.id])}" class="layui-btn layui-btn-normal layui-btn-sm">编辑</a>-->
<!--                        <button data-id="{$v.id}" class="layui-btn layui-btn-sm layui-btn-danger ml0" lay-submit lay-filter="Detele">删除</button>-->
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

        // 测算
        form.on('submit(Calculate)', function(data){
            var text = $(this).text(),
                button = $(this);
            layer.confirm('确定测算吗？',{icon:3,closeBtn:0,title:false,btnAlign:'c'},function(){
                $('button').attr('disabled',true);
                button.text('请稍候...');
                axios.post("{:url('calculate')}", {id:data.field})
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

        $(".calcuRes").click(function(){
            let info = $(this).data('info');
            layer.alert(info,{
                title: "费用详情",
                icon: 7,
                area: ['500px', '180px'],
                btn: ['关闭'],
                btnAlign: 'c'
            });
        });
    });
</script>

{include file="public/footer" /}
