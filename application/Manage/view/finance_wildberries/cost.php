
{include file="public/header" /}

<style>
    .total {padding: 0 10px}
</style>
<!-- 主体内容 -->
<div class="layui-body" id="LAY_app_body">
    <div class="right">
        <a href="{:url('index')}" class="layui-btn layui-btn-danger layui-btn-sm fr"><i class="layui-icon">&#xe603;</i>返回上一页</a>
        <div class="title">野莓成本费用列表</div>
        <form class="layui-form search-form" method="get">
            <div class="layui-inline w200">
                <input type="text" class="layui-input" name="keyword" value="{$keyword}" placeholder="">
            </div>
            <div class="layui-inline w100">
                <input type="text" class="layui-input" name="page_num" value="{$page_num}" placeholder="每页条数">
            </div>
            <div class="layui-inline">
                <button class="layui-btn" lay-submit lay-filter="Search"><i class="layui-icon">&#xe615;</i> 查询</button>
            </div>
        </form>

        <div class="layui-form">
            <button type="button" class="layui-btn  layui-btn-normal" id="fee">费用导入</button>
            <span class="total">下单金额合计：{$cost|number_format=###, 2}</span>
            <span class="total">国内运费合计：{$shipping_fee|number_format=###, 2}</span>
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
                    <th>WB平台订单号</th>
                    <th>产品名称</th>
                    <th>WB产品编号</th>
                    <th>下单时间</th>
                    <th>单价</th>
                    <th>下单数量</th>
                    <th>下单金额</th>
                    <th>国内运费</th>
                    <th>支付月份</th>
                    <th>核算月份</th>
                </tr>
                </thead>
                <tbody>
                {foreach name="list" item="v"}
                <tr>
                    <td>{$v.order_no}</td>
                    <td>{$v.product_name}</td>
                    <td>{$v.sku}</td>
                    <td>{$v.created_date}</td>
                    <td class="tr">{$v.unit_price}</td>
                    <td class="tr">{$v.quantity}</td>
                    <td class="tr">{$v.total}</td>
                    <td class="tr">{$v.shipping_fee}</td>
                    <td class="tr">{$v.month}</td>
                    <td class="tr">{$v.calculate_month}</td>
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

        //执行一个laydate实例
        laydate.render({
            elem: '#month' //指定元素
            ,type: 'month'
        });

        // 上传
        let uploadFee = upload.render({
            elem: '#fee' //绑定元素
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
                    location.href = "/Manage/FinanceWildberries/cost_import/filename/" + res.data + "/origin/" + res.origin;
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
