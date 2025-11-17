
{include file="public/header" /}

<style>
    .layui-table .purchaser_competitor_url {
        max-width: 120px;      /* 设置单元格最大宽度 */
        white-space: nowrap;   /* 防止换行 */
        overflow: hidden;      /* 超出部分隐藏 */
        text-overflow: ellipsis; /* 超出部分显示省略号 */
    }
</style>
<!-- 主体内容 -->
<div class="layui-body" id="LAY_app_body">
    <div class="right">
        <a href="{:url('Quote/table')}" class="layui-btn layui-btn-danger layui-btn-sm fr"><i class="layui-icon">&#xe603;</i>返回上一页</a>
        <div class="title">产品列表</div>

        <div class="layui-form">
            <table class="layui-table" lay-size="sm">
                <colgroup>
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
                </colgroup>
                <thead>
                <tr>
                    <th>采购合同</th>
                    <th>产品编号</th>
                    <th>中文名称</th>
                    <th>外箱长度</th>
                    <th>外箱宽度</th>
                    <th>外箱高度</th>
                    <th>总毛重</th>
                    <th>出货数量</th>
                    <th>总体积</th>
                    <th>采购员</th>
                </tr>
                </thead>
                <tbody>
                {foreach name="list" item="v"}
                <tr>
                    <td>{$v.contact_no}</td>
                    <td>{$v.sku}</td>
                    <td>{$v.sku_cn_name}</td>
                    <td>{$v.carton_length}</td>
                    <td>{$v.carton_width}</td>
                    <td>{$v.carton_height}</td>
                    <td>{$v.total_net_weight}</td>
                    <td>{$v.total_qty}</td>
                    <td>{$v.total_volume}</td>
                    <td>{$v.purchaser}</td>
                </tr>
                {/foreach}
                </tbody>
            </table>
        </div>

    </div>
</div>
<script>
    layui.use(['form', 'upload', 'jquery'], function(){
        let $ = layui.jquery,
            upload = layui.upload,
            form = layui.form;

        // 删除
        form.on('submit(Detele)', function(data){
            var text = $(this).text(),
                button = $(this),
                id = $(this).data('id');
            layer.confirm('确定删除吗？',{icon:3,closeBtn:0,title:false,btnAlign:'c'},function(){
                $('button').attr('disabled',true);
                button.text('请稍候...');
                $.ajax({
                    type:'POST',url:"{:url('delete')}",data:{id:id},dataType:'json',
                    success:function(data){
                        if(data.code === 1){
                            layer.alert(data.msg,{icon:1,closeBtn:0,title:false,btnAlign:'c'},function(){
                                location.reload();
                            });
                        }else{
                            layer.alert(data.msg,{icon:2,closeBtn:0,title:false,btnAlign:'c'},function(){
                                layer.closeAll();
                                $('button').attr('disabled',false);
                                button.text(text);
                            });
                        }
                    }
                });
            });
        });
    });
</script>

{include file="public/footer" /}
