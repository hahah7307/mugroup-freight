
{include file="public/header" /}

<style>
    .total {padding: 0 10px}
</style>
<!-- 主体内容 -->
<div class="layui-body" id="LAY_app_body">
    <div class="right">
        <a href="{:session('back_url', '', 'manage')}" class="layui-btn layui-btn-danger layui-btn-sm fr"><i class="layui-icon">&#xe603;</i>返回上一页</a>
        <div class="title">Wayfair平台账单销售</div>
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
            <span class="total">销售合计：{$sale_amount}</span>
            <span class="total">佣金合计：{$commission}</span>
            <span class="total">应收合计：{$collection}</span>
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
                    <th>发票号</th>
                    <th>订单号</th>
                    <th>发票时间</th>
                    <th>总销售</th>
                    <th>CA佣金</th>
                    <th>AM佣金</th>
                    <th>佣金</th>
                    <th>运费</th>
                    <th>其他</th>
                    <th>税费</th>
                    <th>应收</th>
                    <th>商业</th>
                    <th>订单类型</th>
                    <th>回款批次</th>
                    <th>回款日期</th>
                    <th>发票总销售</th>
                    <th>发票佣金</th>
                    <th>发票应收</th>
                </tr>
                </thead>
                <tbody>
                {foreach name="list" item="v"}
                <tr>
                    <td>{$v.invoice_no}</td>
                    <td>{$v.order_no}</td>
                    <td>{$v.invoice_date}</td>
                    <td class="tr">{$v.amount}</td>
                    <td class="tr">{$v.ca_commission}</td>
                    <td class="tr">{$v.am_commission}</td>
                    <td class="tr">{$v.commission}</td>
                    <td class="tr">{$v.shipping}</td>
                    <td class="tr">{$v.other}</td>
                    <td class="tr">{$v.tax}</td>
                    <td class="tr">{$v.collection}</td>
                    <td>{$v.business}</td>
                    <td>{$v.order_type}</td>
                    <td>{$v.payment_batch}</td>
                    <td>{$v.payment_date}</td>
                    <td class="tr">{$v.wayfair_core.0.sale_amount}</td>
                    <td class="tr">{$v.wayfair_core.0.commission}</td>
                    <td class="tr">{$v.wayfair_core.0.collection}</td>
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
                    location.href = "/Manage/Finance/index_wayfair_import/id/{$report_id}/filename/" + res.data + "/origin/" + res.origin;
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
                axios.post("{:url('index_wayfair_empty')}", {id:id})
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
