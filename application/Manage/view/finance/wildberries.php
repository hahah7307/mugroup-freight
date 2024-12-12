
{include file="public/header" /}

<!-- 主体内容 -->
<div class="layui-body" id="LAY_app_body">
    <div class="right">
        <div class="title">野莓订单列表</div>
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
            <div class="layui-inline">
                <a class="layui-btn layui-btn-normal" href="{:url('order_statistics')}"><i class="layui-icon">&#xe669;</i> 重置</a>
            </div>
        </form>

        <div class="layui-form">
            <button type="button" class="layui-btn  layui-btn-normal" id="order">归档订单导入</button>
            <button type="button" class="layui-btn  layui-btn-normal" id="fee">费用导入</button>
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
                    <col>
                </colgroup>
                <thead>
                <tr>
                    <th>工作編號</th>
                    <th>發貨二維碼</th>
                    <th>貼紙</th>
                    <th>創建日期</th>
                    <th>姓名</th>
                    <th>尺寸</th>
                    <th>顏色</th>
                    <th>價格</th>
                    <th>貨幣</th>
                    <th>文章 野莓</th>
                    <th>賣家的文章</th>
                    <th>賣家倉庫</th>
                    <th>工作狀態</th>
                    <th>項目掃描日期</th>
                    <th>В訂單起算時間</th>
                    <th>产品成本</th>
                    <th>国内运费</th>
                </tr>
                </thead>
                <tbody>
                {foreach name="list" item="v"}
                <tr>
                    <td>{$v.order_no}</td>
                    <td>{$v.shipping_no}</td>
                    <td>{$v.fbs_no}</td>
                    <td>{$v.created_date}</td>
                    <td>{$v.name}</td>
                    <td>{$v.size}</td>
                    <td>{$v.color}</td>
                    <td>{$v.total}</td>
                    <td>{$v.currency}</td>
                    <td>{$v.article_wildberries}</td>
                    <td>{$v.article_seller}</td>
                    <td>{$v.warehouse_name}</td>
                    <td>{$v.status}</td>
                    <td>{$v.item_scan_date}</td>
                    <td>{$v.order_time}</td>
                    <td>{$v.fee.total}</td>
                    <td>{$v.fee.shipping_fee}</td>
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
        let uploadInst = upload.render({
            elem: '#order' //绑定元素
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
                    location.href = "/Manage/Finance/wildberries_import/filename/" + res.data + "/origin/" + res.origin;
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
                    location.href = "/Manage/Finance/wildberries_fee_import/filename/" + res.data + "/origin/" + res.origin;
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
