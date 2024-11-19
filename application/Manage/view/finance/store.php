
{include file="public/header" /}

<style>
    .total {padding: 0 10px}
</style>
<!-- 主体内容 -->
<div class="layui-body" id="LAY_app_body">
    <div class="right">
        <a href="{:session('back_url', '', 'manage')}" class="layui-btn layui-btn-danger layui-btn-sm fr"><i class="layui-icon">&#xe603;</i>返回上一页</a>
        <div class="title">库存列表</div>
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
            <button data-id="{$report_id}" class="layui-btn layui-btn-danger ml0" lay-submit lay-filter="Detele">清空</button>
            <span class="total">未结算数量合计：{$available_qty|number_format=###,2}</span>
            <span class="total">未结算金额合计：{$available_sum|number_format=###,2}</span>
            <span class="total">计提金额合计：{$accrual_total|number_format=###,2}</span>
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
                    <col>
                </colgroup>
                <thead>
                <tr>
                    <th>ID</th>
                    <th>入库币种</th>
                    <th>入库日期</th>
                    <th>出运日期</th>
                    <th>到港日期</th>
                    <th>产品编号</th>
                    <th>中文品名</th>
                    <th>采购合同号</th>
                    <th>外销编号</th>
                    <th>入库数量</th>
                    <th>采购单价</th>
                    <th>采购总价</th>
                    <th>含头程价</th>
                    <th>成本汇总</th>
                    <th>已结算数量</th>
                    <th>未结算数量</th>
                    <th>运营</th>
                    <th>采购</th>
                    <th>库龄</th>
                    <th>计提日期</th>
                    <th>计提金额</th>
                </tr>
                </thead>
                <tbody>
                {foreach name="list" item="v"}
                <tr>
                    <td class="tr">{$v.id}</td>
                    <td>{$v.currency}</td>
                    <td class="tr">{$v.entering_date}</td>
                    <td class="tr">{$v.shipment_date}</td>
                    <td class="tr">{$v.arriving_date}</td>
                    <td>{$v.sku}</td>
                    <td>{$v.cn_name}</td>
                    <td>{$v.contact_no}</td>
                    <td>{$v.export_no}</td>
                    <td class="tr">{$v.entering_quantity}</td>
                    <td class="tr">{$v.sku_purchase_unit}</td>
                    <td class="tr">{$v.sku_purchase_amount}</td>
                    <td class="tr">{$v.sku_ddp_unit}</td>
                    <td class="tr">{$v.sku_ddp_amount}</td>
                    <td class="tr">{$v.outbound_quantity}</td>
                    <td class="tr">{$v.available_quantity}</td>
                    <td>{$v.seller}</td>
                    <td>{$v.purchaser}</td>
                    <td class="tr">{$v.days}</td>
                    <td class="tr">{$v.accrual_date}</td>
                    <td class="tr">{$v.accrual_total}</td>
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

        // 导入
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
                    location.href = "/Manage/Finance/store_import/id/{$report_id}/filename/" + res.data + "/origin/" + res.origin;
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
            layer.confirm('确定清空库存列表吗？',{icon:3,closeBtn:0,title:false,btnAlign:'c'},function(){
                $('button').attr('disabled',true);
                button.text('请稍候...');
                axios.post("{:url('store_empty')}", {id:id})
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
