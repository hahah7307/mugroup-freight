
{include file="public/header" /}

<style>
    .total {padding: 0 10px}
</style>
<!-- 主体内容 -->
<div class="layui-body" id="LAY_app_body">
    <div class="right">
        <div class="title">Wayfair订单列表</div>
        <form class="layui-form search-form" method="get">
            <div class="layui-inline w200">
                <input type="text" class="layui-input" name="keyword" value="{$keyword}" placeholder="PAYMENT/核算月份">
            </div>
            <div class="layui-inline w100">
                <input type="text" class="layui-input" name="page_num" value="{$page_num}" placeholder="每页条数">
            </div>
            <div class="layui-inline">
                <button class="layui-btn" lay-submit lay-filter="Search"><i class="layui-icon">&#xe615;</i> 查询</button>
            </div>
            <div class="layui-inline">
                <a class="layui-btn layui-btn-normal" href="{:url('wayfair_core')}"><i class="layui-icon">&#xe669;</i> 重置</a>
            </div>
        </form>

        <div class="layui-form">
            <button type="button" class="layui-btn  layui-btn-normal" id="excel">导入</button>
            <span class="total">订单金额合计：{$amount}</span>
            <span class="total">佣金合计：{$commission}</span>
            <span class="total">回款金额合计：{$collection}</span>
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
                </colgroup>
                <thead>
                <tr>
                    <th>ID</th>
                    <th>店铺</th>
                    <th>发票号</th>
                    <th>发票时间</th>
                    <th>订单号</th>
                    <th>类型</th>
                    <th>订单金额</th>
                    <th>佣金比例</th>
                    <th>发票佣金</th>
                    <th>发票应收</th>
                    <th>币种</th>
                    <th>核算月份</th>
                    <th>回款金额</th>
                    <th>回款日期</th>
                    <th>回款批次</th>
                    <th>回款周期</th>
                    <th>操作</th>
                </tr>
                </thead>
                <tbody>
                {foreach name="list" item="v"}
                <tr>
                    <td class="tr">{$v.id}</td>
                    <td>{$v.user_account}</td>
                    <td>{$v.invoice_no}</td>
                    <td>{$v.invoice_date}</td>
                    <td>{$v.payment_id}</td>
                    <td>
                        {if condition="$v.status eq 1"}
                        已发货
                        {elseif condition="$v.status eq 2"/}
                        退款
                        {else/}
                        调整
                        {/if}
                    </td>
                    <td class="tr">{$v.sale_amount}</td>
                    <td class="tr">{$v.commission_rate}</td>
                    <td class="tr">{$v.commission}</td>
                    <td class="tr">{$v.collection}</td>
                    <td>{$v.currency}</td>
                    <td class="tr">{$v.calculate_month}</td>
                    <td class="tr">{$v.payment_collection}</td>
                    <td>{$v.payment_date}</td>
                    <td>{$v.payment_batch}</td>
                    <td class="tr">{$v.payment_cycle}</td>
                    <td class="tc">
                        <a href="{:url('wayfair_core_edit', ['id' => $v.id])}" class="layui-btn layui-btn-normal layui-btn-sm">修正</a>
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
                    location.href = "/Manage/Finance/wayfair_import/filename/" + res.data + "/origin/" + res.origin;
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
