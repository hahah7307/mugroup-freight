
{include file="public/header" /}

<!-- 主体内容 -->
<div class="layui-body" id="LAY_app_body">
    <div class="right">
        <a href="{:url('report')}" class="layui-btn layui-btn-danger layui-btn-sm fr"><i class="layui-icon">&#xe603;</i>返回上一页</a>
        <div class="title">映射关系列表</div>
        <form class="layui-form search-form" method="get">
            <div class="layui-inline w200">
                <input type="text" class="layui-input" name="keyword" value="{$keyword}" placeholder="sku/店铺/运营">
            </div>
            <div class="layui-inline w100">
                <input type="text" class="layui-input" name="page_num" value="{$page_num}" placeholder="每页条数">
            </div>
            <div class="layui-inline">
                <button class="layui-btn" lay-submit lay-filter="Search"><i class="layui-icon">&#xe615;</i> 查询</button>
            </div>
        </form>

        <div class="layui-form">
            <a class="layui-btn" href="{:url('relation_add', ['id' => $report_id])}">添加</a>
            <button type="button" class="layui-btn  layui-btn-normal" id="excel">导入</button>
            <button data-id="{$report_id}" class="layui-btn layui-btn-danger ml0" lay-submit lay-filter="Detele">清空</button>
            <table class="layui-table" lay-size="sm">
                <colgroup>
                    <col>
                    <col>
                    <col>
                    <col>
                    <col>
                </colgroup>
                <thead>
                <tr>
                    <th>店铺SKU</th>
                    <th>仓库SKU</th>
                    <th>数量</th>
                    <th>单价</th>
                    <th>产品名称</th>
                    <th>占比</th>
                    <th>所在仓库</th>
                    <th>店铺名</th>
                    <th>创建人</th>
                    <th>创建时间</th>
                    <th>更新时间</th>
                    <th>运营人员</th>
                    <th class="tc">操作</th>
                </tr>
                </thead>
                <tbody>
                {foreach name="list" item="v"}
                <tr>
                    <td>{$v.seller_sku}</td>
                    <td>{$v.warehouse_sku}</td>
                    <td class="tr">{$v.qty}</td>
                    <td class="tr">{$v.unit_price}</td>
                    <td>{$v.product_name}</td>
                    <td class="tr">{$v.percent}</td>
                    <td>{$v.warehouse_name}</td>
                    <td>{$v.user_account}</td>
                    <td>{$v.created_user}</td>
                    <td class="tc">{$v.created_date}</td>
                    <td class="tc">{$v.updated_date}</td>
                    <td>{$v.seller}</td>
                    <td class="tc">
                        <a href="{:url('relation_edit', ['id' => $v.id])}" class="layui-btn layui-btn-normal layui-btn-sm">编辑</a>
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
    layui.use(['form', 'jquery', 'upload', 'laydate'], function(){
        let $ = layui.jquery,
            form = layui.form,
            upload = layui.upload,
            laydate = layui.laydate;

        // 上传
        let uploadInst = upload.render({
            elem: '#excel' //绑定元素
            ,url: '/Manage/upload/file_upload' //上传接口
            ,exts: 'xls|xlsx|csv'
            ,multiple: true
            ,before: function (obj){
                layer.load(1);
            }
            ,done: function(res){
                //上传完毕回调
                if (res.code === 1) {
                    location.href = "/Manage/Finance/relation_import/id/{$report_id}/filename/" + res.data + "/origin/" + res.origin;
                } else {
                    layer.alert(res.msg,{icon:2,closeBtn:0,title:false,btnAlign:'c'},function(){
                        layer.closeAll();
                    });
                }
            }
            ,error: function(){
                //请求异常回调
            }
        });

        // 清空
        form.on('submit(Detele)', function(data){
            var text = $(this).text(),
                button = $(this),
                id = $(this).data('id');
            layer.confirm('确定清空列表吗？',{icon:3,closeBtn:0,title:false,btnAlign:'c'},function(){
                $('button').attr('disabled',true);
                button.text('请稍候...');
                axios.post("{:url('relation_empty')}", {id:id})
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

        // 删除
        form.on('submit(Detele)', function(data){
            var text = $(this).text(),
                button = $(this),
                id = $(this).data('id');
            layer.confirm('确定删除吗？',{icon:3,closeBtn:0,title:false,btnAlign:'c'},function(){
                $('button').attr('disabled',true);
                button.text('请稍候...');
                axios.post("{:url('relation_delete')}", {id:id})
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
