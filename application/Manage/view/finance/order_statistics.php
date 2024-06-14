
{include file="public/header" /}

<!-- 主体内容 -->
<div class="layui-body" id="LAY_app_body">
    <div class="right">
        <a href="{:session('back_url', '', 'manage')}" class="layui-btn layui-btn-danger layui-btn-sm fr"><i class="layui-icon">&#xe603;</i>返回上一页</a>
        <div class="title">订单统计列表</div>
        <form class="layui-form search-form" method="get">
            <div class="layui-inline w200">
                <input type="text" class="layui-input" name="keyword" value="{$keyword}" placeholder="SKU/外销合同/采购合同号">
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
                    <th>平台</th>
                    <th>店铺</th>
                    <th>发货时间</th>
                    <th>订单状态</th>
                    <th>仓库单号</th>
                    <th>跟踪号</th>
                    <th>发货批次号</th>
                    <th>Payment</th>
                    <th>参考号</th>
                    <th>平台sku</th>
                    <th>仓库sku</th>
                    <th>数量</th>
                    <th>单价</th>
                    <th>总额</th>
                    <th>运费</th>
                    <th>佣金</th>
                    <th>FBA尾程</th>
                    <th>税</th>
                    <th>操作</th>
                </tr>
                </thead>
                <tbody>
                {foreach name="list" item="v"}
                <tr>
                    <td>{$v.platform}</td>
                    <td>{$v.user_account}</td>
                    <td>{$v.shipping_time}</td>
                    <td>{$v.order_status}</td>
                    <td>{$v.warehouse_no}</td>
                    <td>{$v.shipping_no}</td>
                    <td>{$v.inventory_batch_no}</td>
                    <td>{$v.payment_id}</td>
                    <td>{$v.saleOrderCode}</td>
                    <td>{$v.platform_sku}</td>
                    <td>{$v.warehouse_sku}</td>
                    <td class="tr">{$v.qty}</td>
                    <td class="tr">{$v.sale_unit}</td>
                    <td class="tr">{$v.sale_amount}</td>
                    <td class="tr">{$v.sale_shipping}</td>
                    <td class="tr">{$v.selling_fee}</td>
                    <td class="tr">{$v.fba_fee}</td>
                    <td class="tr">{$v.tax}</td>
                    <td class="tc">
                        <a href="{:url('order_statistics_edit', ['id' => $v.id])}" class="layui-btn layui-btn-normal layui-btn-sm">编辑</a>
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
                console.log(res);
                if (res.code === 1) {
                    location.href = "/Manage/Finance/order_statistics_import/filename/" + res.data + "/origin/" + res.origin;
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
