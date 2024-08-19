
{include file="public/header" /}

<style>
    .total {padding: 0 10px}
</style>
<!-- 主体内容 -->
<div class="layui-body" id="LAY_app_body">
    <div class="right">
        <a href="{:url('cost', ['id' => $report_id])}" class="layui-btn layui-btn-danger layui-btn-sm fr"><i class="layui-icon">&#xe603;</i>返回上一页</a>
        <div class="title">额外四项费用明细</div>
        <form class="layui-form search-form" method="get">
            <div class="layui-inline w200">
                <input type="text" class="layui-input" name="keyword" value="{$keyword}" placeholder="SKU/店铺名">
            </div>
            <div class="layui-inline w100">
                <input type="text" class="layui-input" name="page_num" value="{$page_num}" placeholder="每页条数">
            </div>
            <div class="layui-inline">
                <button class="layui-btn" lay-submit lay-filter="Search"><i class="layui-icon">&#xe615;</i> 查询</button>
            </div>
        </form>

        <div class="layui-form">
            <button type="button" class="layui-btn  layui-btn-normal" id="excel">导入</button>
            <button data-id="{$report_id}" class="layui-btn layui-btn-danger ml0" lay-submit lay-filter="Detele">清空</button>
            <span class="total">调整合计：{$adjustment|number_format=###,2}</span>
            <span class="total">清算合计：{$liquidation|number_format=###,2}</span>
            <span class="total">促销合计：{$promotion|number_format=###,2}</span>
            <span class="total">退运合计：{$shipping_service|number_format=###,2}</span>
            <span class="total">良仓调整合计：{$lc_adjustment|number_format=###,2}</span>
            <span class="total">乐歌调整合计：{$le_adjustment|number_format=###,2}</span>
            <span class="total">WFS调整合计：{$wfs_adjustment|number_format=###,2}</span>
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
                </colgroup>
                <thead>
                <tr>
                    <th>平台</th>
                    <th>店铺</th>
                    <th>仓库SKU</th>
                    <th>索赔(调整)费用</th>
                    <th>清算费用</th>
                    <th>促销费用</th>
                    <th>退运费用</th>
                    <th>良仓调整费用</th>
                    <th>乐歌调整费用</th>
                    <th>WFS调整费用</th>
                    <th>分摊唯一标识号</th>
                </tr>
                </thead>
                <tbody>
                {foreach name="list" item="v"}
                <tr>
                    <td>{$v.platform}</td>
                    <td>{$v.user_account}</td>
                    <td>{$v.warehouse_sku}</td>
                    <td class="tr">{$v.claimant}</td>
                    <td class="tr">{$v.liquidation}</td>
                    <td class="tr">{$v.promotion}</td>
                    <td class="tr">{$v.shipping_service}</td>
                    <td class="tr">{$v.lc_adjustment}</td>
                    <td class="tr">{$v.le_adjustment}</td>
                    <td class="tr">{$v.wfs_adjustment}</td>
                    <td>{$v.share_code}</td>
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
                    location.href = "/Manage/Finance/additional_import/id/{$report_id}/filename/" + res.data + "/origin/" + res.origin;
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
            layer.confirm('确定清空列表吗？',{icon:3,closeBtn:0,title:false,btnAlign:'c'},function(){
                $('button').attr('disabled',true);
                button.text('请稍候...');
                axios.post("{:url('additional_empty')}", {id:id})
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
