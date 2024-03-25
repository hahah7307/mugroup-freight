
{include file="public/header" /}

<!-- 主体内容 -->
<div class="layui-body" id="LAY_app_body">
    <div class="right">
        <a href="{:session('back_url', '', 'manage')}" class="layui-btn layui-btn-danger layui-btn-sm fr"><i class="layui-icon">&#xe603;</i>返回上一页</a>
        <div class="title">库存列表</div>
        <form class="layui-form search-form" method="get">
            <div class="layui-inline w200">
                <input type="text" class="layui-input" name="keyword" value="{$keyword}" placeholder="原始单号">
            </div>
            <div class="layui-inline w100">
                <input type="text" class="layui-input" name="page_num" value="{$page_num}" placeholder="每页条数">
            </div>
            <div class="layui-inline">
                <button class="layui-btn" lay-submit lay-filter="Search"><i class="layui-icon">&#xe615;</i> 查询</button>
            </div>
            <div class="layui-inline">
                <a class="layui-btn layui-btn-normal" href="{:url('index')}"><i class="layui-icon">&#xe669;</i> 重置</a>
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
                    <col>
                    <col>
                </colgroup>
                <thead>
                <tr>
                    <th class="tc">
                        <input type="checkbox" lay-skin="primary" id="YanNanQiu_checkall" lay-filter="YanNanQiu_checkall">
                    </th>
                    <th>入库时间</th>
                    <th>入库币种</th>
                    <th>数量合计</th>
                    <th>采购金额合计</th>
                    <th>成本汇总合计</th>
                    <th>到港时间</th>
                    <th>外销合同</th>
                    <th>出运日期</th>
                    <th>产品编号</th>
                    <th>中文品名</th>
                    <th>入库数量</th>
                    <th>采购单价</th>
                    <th>采购总价</th>
                    <th>含头程价</th>
                    <th>成本汇总</th>
                    <th>已结算数量</th>
                    <th>未结算数量</th>
                    <th>运营</th>
                    <th>采购</th>
                    <th>采购合同号</th>
                </tr>
                </thead>
                <tbody>
                {foreach name="list" item="v"}
                <tr>
                    <td class="tc">
                        <div class="YanNanQiu_Checkbox">
                            <input type="checkbox" name="id[]" lay-skin="primary" lay-filter="imgbox" class="YanNanQiu_imgId" value="{$v.id}">
                        </div>
                    </td>
                    <td>{$v.entering_date}</td>
                    <td>{$v.currency}</td>
                    <td>{$v.quantity_amount}</td>
                    <td>{$v.purchase_amount}</td>
                    <td>{$v.cost_amount}</td>
                    <td>{$v.arriving_date}</td>
                    <td>{$v.export_no}</td>
                    <td>{$v.shipment_date}</td>
                    <td>{$v.sku}</td>
                    <td>{$v.cn_name}</td>
                    <td>{$v.entering_quantity}</td>
                    <td>{$v.sku_purchase_unit}</td>
                    <td>{$v.sku_purchase_amount}</td>
                    <td>{$v.sku_ddp_unit}</td>
                    <td>{$v.sku_ddp_amount}</td>
                    <td>{$v.outbound_quantity}</td>
                    <td>{$v.available_quantity}</td>
                    <td>{$v.seller}</td>
                    <td>{$v.purchaser}</td>
                    <td>{$v.content}</td>
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
    });
</script>

{include file="public/footer" /}
