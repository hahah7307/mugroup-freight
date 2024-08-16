
{include file="public/header" /}

<style>
    .total {padding: 0 10px}
</style>
<!-- 主体内容 -->
<div class="layui-body" id="LAY_app_body">
    <div class="right">
        <a href="{:url('report')}" class="layui-btn layui-btn-danger layui-btn-sm fr"><i class="layui-icon">&#xe603;</i>返回上一页</a>
        <div class="title">费用类列表</div>

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
                    <td>仓租</td>
                    <td>海外仓仓储费（FBM仓储费）</td>
                    <td class="tc">
                        <a href="{:url('warehouse', ['id' => $id])}" class="layui-btn layui-btn layui-btn-sm">查看</a>
                    </td>
                </tr>
                <tr>
                    <td>额外</td>
                    <td>额外四项费用（包含运营分摊的促销费用、良仓调整费用、乐歌调整费用、WFS调整费用等）</td>
                    <td class="tc">
                        <a href="{:url('additional', ['id' => $id])}" class="layui-btn layui-btn layui-btn-sm">查看</a>
                    </td>
                </tr>
                <tr>
                    <td>测评</td>
                    <td>测评费用（系统会根据实际出库进行分摊，且币种换算成美金）</td>
                    <td class="tc">
                        <a href="{:url('evaluation', ['id' => $id])}" class="layui-btn layui-btn layui-btn-sm">查看</a>
                    </td>
                </tr>
                <tr>
                    <td>广告</td>
                    <td>其他平台广告费（此处特指其他平台广告费，亚马逊平台广告费由领星提供系统自动分摊且无分摊数据留存）</td>
                    <td class="tc">
                        <a href="{:url('ad_cost', ['id' => $id])}" class="layui-btn layui-btn layui-btn-sm">查看</a>
                    </td>
                </tr>
                <tr>
                    <td>台账-国内广告</td>
                    <td>国内广告费（包含）</td>
                    <td class="tc">
                        <a href="{:url('operation_expenses', ['id' => $id])}" class="layui-btn layui-btn layui-btn-sm">查看</a>
                    </td>
                </tr>
                <tr>
                    <td>台账-工厂</td>
                    <td>国内广告费（包含）</td>
                    <td class="tc">
                        <a href="{:url('operation_factory', ['id' => $id])}" class="layui-btn layui-btn layui-btn-sm">查看</a>
                    </td>
                </tr>
                <tr>
                    <td>台账-国内快递</td>
                    <td>国内广告费（包含）</td>
                    <td class="tc">
                        <a href="{:url('operation_delivery', ['id' => $id])}" class="layui-btn layui-btn layui-btn-sm">查看</a>
                    </td>
                </tr>
                </tbody>
            </table>
        </div>

    </div>
</div>
<script>
    layui.use(['form', 'jquery', 'upload', 'laydate'], function(){
        let $ = layui.jquery,
            form = layui.form,
            upload = layui.upload;

        // 导入
        let uploadInst = upload.render({
            elem: '#excel' //绑定元素
            ,url: '/Manage/upload/finance_order_upload' //上传接口
            ,exts: 'xls|xlsx|csv'
            ,data: {
                payment_type: function(){
                    return $("#payment_type").val();
                }
            }
            ,multiple: true
            ,before: function (obj){
                layer.load(1);
            }
            ,done: function(res){
                //上传完毕回调
                if (res.code === 1) {
                    location.href = "/Manage/Finance/import/rid/{$rid}/filename/" + encodeURIComponent(res.data) + "/origin/" + res.origin + "/payment_type/" + res.payment_type;
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

        // 删除
        form.on('submit(Detele)', function(data){
            var text = $(this).text(),
                button = $(this),
                id = $(this).data('id');
            layer.confirm('确定删除吗？',{icon:3,closeBtn:0,title:false,btnAlign:'c'},function(){
                $('button').attr('disabled',true);
                button.text('请稍候...');
                axios.post("{:url('table_delete')}", {id:id})
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

        // 修正
        form.on('submit(Edit)', function(data){
            var text = $(this).text(),
                button = $(this),
                id = {$report_id};
            layer.confirm('确认修正吗？请先确保销售金额已核对',{icon:3,closeBtn:0,title:false,btnAlign:'c'},function(){
                $('button').attr('disabled',true);
                button.text('请稍候...');
                axios.post("{:url('order_statistics_edit_auto')}", {id:id})
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
