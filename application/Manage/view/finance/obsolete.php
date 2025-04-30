
{include file="public/header" /}

<style>
    .total {padding: 0 10px}
</style>
<!-- 主体内容 -->
<div class="layui-body" id="LAY_app_body">
    <div class="right">
        <div class="title">滞销SKU列表</div>
        <form class="layui-form search-form" method="get">
            <div class="layui-input-inline">
                <input type="text" class="layui-input" id="month" name="month" value="{$month}" placeholder="核算月份">
            </div>
            <div class="layui-inline w200">
                <input type="text" class="layui-input" name="keyword" value="{$keyword}" placeholder="">
            </div>
            <div class="layui-inline">
                <button class="layui-btn" lay-submit lay-filter="Search"><i class="layui-icon">&#xe615;</i> 查询</button>
            </div>
        </form>

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
                <col>
            </colgroup>
            <thead>
            <tr>
                <th>类型</th>
                <th>SKU</th>
                <th>原承担人</th>
                <th>现承担人</th>
                <th>仓库数量</th>
                <th>本地数量</th>
                <th>日销数量</th>
                <th>占比</th>
                <th>描述</th>
            </tr>
            </thead>
            <tbody>
            {foreach name="list" item="v"}
            <tr>
                <td>
                    {if condition="$v.type eq 1"}
                    组内交接
                    {elseif condition="$v.type eq 2"/}
                    共同销售
                    {/if}
                </td>
                <td>{$v.sku}</td>
                <td>{$v.seller_original}</td>
                <td>{$v.seller_current}</td>
                <td class="tr">{$v.warehouse_stock}</td>
                <td class="tr">{$v.local_stock}</td>
                <td class="tr">{$v.daily_sale}</td>
                <td class="tr">{$v.percent}</td>
                <td>{$v.content}</td>
            </tr>
            {/foreach}
            </tbody>
        </table>
        {$list->render()}
    </div>
</div>
<script>
    layui.use(['form', 'jquery', 'laydate', 'upload'], function(){
        let $ = layui.jquery,
            form = layui.form,
            laydate = layui.laydate,
            upload = layui.upload;

        //执行一个laydate实例
        laydate.render({
            elem: '#month' //指定元素
            ,type: 'month'
        });

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
                    location.href = "/Manage/Finance/obsolete_import/filename/" + res.data + "/month/{$month}";
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
