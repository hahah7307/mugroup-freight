
{include file="public/header" /}

<!-- 主体内容 -->
<div class="layui-body" id="LAY_app_body">
    <div class="right">
        <a href="{:session('back_url', '', 'manage')}" class="layui-btn layui-btn-danger layui-btn-sm fr"><i class="layui-icon">&#xe603;</i>返回上一页</a>
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
                </colgroup>
                <thead>
                <tr>
                    <th>店铺</th>
                    <th>仓库SKU</th>
                    <th>索赔费用</th>
                    <th>清算费用</th>
                    <th>促销费用</th>
                    <th>退运费用</th>
                    <th>良仓调整费用</th>
                    <th>乐歌调整费用</th>
                </tr>
                </thead>
                <tbody>
                {foreach name="list" item="v"}
                <tr>
                    <td>{$v.user_account}</td>
                    <td>{$v.warehouse_sku}</td>
                    <td>{$v.claimant}</td>
                    <td>{$v.liquidation}</td>
                    <td>{$v.promotion}</td>
                    <td>{$v.shipping_service}</td>
                    <td>{$v.lc_adjustment}</td>
                    <td>{$v.le_adjustment}</td>
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
            ,url: '/manage/upload/file_upload' //上传接口
            ,exts: 'xls|xlsx|csv'
            ,multiple: true
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
    });
</script>

{include file="public/footer" /}
