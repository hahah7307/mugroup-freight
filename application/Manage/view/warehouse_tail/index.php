
{include file="public/header" /}

<style>
    .total {padding: 0 10px}
</style>
<!-- 主体内容 -->
<div class="layui-body" id="LAY_app_body">
    <div class="right">
        <div class="title">海外仓尾程列表</div>
        <form class="layui-form search-form" method="get">
            <div class="layui-inline w200">
                <input type="text" class="layui-input" name="keyword" value="{$keyword}" placeholder="">
            </div>
            <div class="layui-inline">
                <button class="layui-btn" lay-submit lay-filter="Search"><i class="layui-icon">&#xe615;</i> 查询</button>
            </div>
        </form>

        <div class="layui-input-inline">
            <input type="text" class="layui-input" id="month" name="month" value="" placeholder="账单月份">
        </div>
        <button type="button" class="layui-btn  layui-btn-normal" id="excel">导入</button>
        <span class="total">尾程合计：{$totalSum|number_format=###,2}</span>

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
                <th>参考单号</th>
                <th>运单号</th>
                <th>平台订单号</th>
                <th>仓库编号</th>
                <th>订单状态</th>
                <th>邮编</th>
                <th>发货时间</th>
                <th>分区号</th>
                <th>SKU</th>
                <th>计费重</th>
                <th>实重</th>
                <th>长</th>
                <th>宽</th>
                <th>高</th>
                <th>出库费</th>
                <th>基础运费</th>
                <th>尾程</th>
                <th>月份</th>
                <th>仓库</th>
            </tr>
            </thead>
            <tbody>
            {foreach name="list" item="v"}
            <tr>
                <td>{$v.saleOrderCode}</td>
                <td>{$v.shipmentNo}</td>
                <td>{$v.refNo}</td>
                <td>{$v.warehouseCode}</td>
                <td>{$v.status}</td>
                <td>{$v.postalCode}</td>
                <td class="tr">{$v.shippingDate}</td>
                <td class="tr">{$v.zone}</td>
                <td>{$v.sku}</td>
                <td class="tr">{$v.charge_weight}</td>
                <td class="tr">{$v.real_weight}</td>
                <td class="tr">{$v.length}</td>
                <td class="tr">{$v.width}</td>
                <td class="tr">{$v.height}</td>
                <td class="tr">{$v.outbound}</td>
                <td class="tr">{$v.base}</td>
                <td class="tr">{$v.total}</td>
                <td class="tr">{$v.month}</td>
                <td class="tr">{$v.warehouseName}</td>
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
                    location.href = "/Manage/WarehouseTail/import/filename/" + res.data + "/month/" + $("#month").val();
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
