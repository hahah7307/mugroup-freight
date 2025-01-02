
{include file="public/header" /}

<style>
    .total {padding: 0 10px}
</style>
<!-- 主体内容 -->
<div class="layui-body" id="LAY_app_body">
    <div class="right">
        <a href="{:url('index')}" class="layui-btn layui-btn-danger layui-btn-sm fr"><i class="layui-icon">&#xe603;</i>返回上一页</a>
        <div class="title">国内快递列表</div>
        <form class="layui-form search-form" method="get">
            <div class="layui-inline w200">
                <input type="text" class="layui-input" name="keyword" value="{$keyword}" placeholder="">
            </div>
            <div class="layui-input-inline">
                <input type="text" class="layui-input" id="month" name="month" value="{$month}" placeholder="支付月份">
            </div>
            <div class="layui-inline w100">
                <input type="text" class="layui-input" name="page_num" value="{$page_num}" placeholder="每页条数">
            </div>
            <div class="layui-inline">
                <button class="layui-btn" lay-submit lay-filter="Search"><i class="layui-icon">&#xe615;</i> 查询</button>
            </div>
        </form>

        <div class="layui-form">
            <button type="button" class="layui-btn  layui-btn-normal" id="express_delivery">国内快递导入</button>
            <span class="total">合计：{$sum|number_format=###, 2}</span>
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
                </colgroup>
                <thead>
                <tr>
                    <th>日期</th>
                    <th>单号</th>
                    <th>重量（公斤）</th>
                    <th>省份</th>
                    <th>收件人</th>
                    <th>目的省份</th>
                    <th>目的城市</th>
                    <th>收件地址</th>
                    <th>寄件地址</th>
                    <th>寄件人</th>
                    <th>公司</th>
                    <th>组别</th>
                    <th>单件计</th>
                    <th>支付月份</th>
                </tr>
                </thead>
                <tbody>
                {foreach name="list" item="v"}
                <tr>
                    <td>{$v.created_date}</td>
                    <td>{$v.delivery_no}</td>
                    <td class="tr">{$v.weight}</td>
                    <td>{$v.province}</td>
                    <td>{$v.receiver}</td>
                    <td>{$v.target_province}</td>
                    <td>{$v.target_city}</td>
                    <td>{$v.receiver_addr}</td>
                    <td>{$v.sender_addr}</td>
                    <td>{$v.sender}</td>
                    <td>{$v.company_name}</td>
                    <td>{$v.group_name}</td>
                    <td class="tr">{$v.total}</td>
                    <td class="tr">{$v.month}</td>
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
            elem: '#express_delivery' //绑定元素
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
                    location.href = "/Manage/FinanceWildberries/express_delivery_import/filename/" + res.data + "/origin/" + res.origin;
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
