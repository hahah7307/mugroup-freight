
{include file="public/header" /}

<style>
    .total {padding: 0 10px}
</style>
<!-- 主体内容 -->
<div class="layui-body" id="LAY_app_body">
    <div class="right">
        <a href="{:url('report')}" class="layui-btn layui-btn-danger layui-btn-sm fr"><i class="layui-icon">&#xe603;</i>返回上一页</a>
        <div class="title">Payment列表</div>
        <form class="layui-form search-form" method="get">
            <div class="layui-inline w200">
                <input type="text" class="layui-input" name="keyword" value="{$keyword}" placeholder="文件名称">
            </div>
            <div class="layui-inline">
                <button class="layui-btn" lay-submit lay-filter="Search"><i class="layui-icon">&#xe615;</i> 查询</button>
            </div>
        </form>

        <div class="layui-form">
            <div class="layui-input-inline w180">
                <select name="payment_type" id="payment_type">
                    <option value="">请选择账单类型</option>
                    <option value="amazon_us">amazon_us</option>
                    <option value="amazon_uk">amazon_uk</option>
                    <option value="amazon_de">amazon_de</option>
                    <option value="amazon_es">amazon_es</option>
                    <option value="amazon_fr">amazon_fr</option>
                    <option value="amazon_it">amazon_it</option>
                    <option value="wayfair">wayfair</option>
                    <option value="walmart">walmart</option>
                    <option value="shein">shein</option>
                    <option value="shein_semi_managed">shein_semi_managed</option>
                    <option value="temu">temu</option>
                    <option value="temu_detail">temu_detail</option>
                    <option value="ebay">ebay</option>
                </select>
            </div>
            <button type="button" class="layui-btn  layui-btn-normal" id="excel">导入</button><br><br>
            <a href="{:url('index_wayfair', ['id' => $report_id])}" class="layui-btn">Wayfair</a>
            <button type="button" class="layui-btn  layui-btn-{if condition='$edit'}disabled{else/}normal{/if}" lay-submit lay-filter="Edit">修正</button>
            <span class="total">销售合计：{$sale_amount}</span>
            <span class="total">退款合计：{$refund_amount}</span>
            <span class="total">促销合计：{$promotion}</span>
            <span class="total">退运合计：{$shipping_service}</span>
            <span class="total">清算合计：{$liquidation}</span>
            <span class="total">调整合计：{$adjustment}</span>
            <table class="layui-table" lay-size="sm">
                <colgroup>
                    <col width="50">
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
                    <col width="140">
                    <col width="180">
                </colgroup>
                <thead>
                <tr>
                    <th class="tc">ID</th>
                    <th>导入表格名</th>
                    <th>所属平台</th>
                    <th>所属店铺</th>
                    <th>所属国家</th>
                    <th>销售</th>
                    <th>退款</th>
                    <th>促销费</th>
                    <th>退运费</th>
                    <th>清算</th>
                    <th>调整</th>
                    <th>导入时间</th>
                    <th class="tc">操作</th>
                </tr>
                </thead>
                <tbody>
                {foreach name="list" item="v"}
                <tr>
                    <td class="tc">{$v.id}</td>
                    <td>{$v.table_name}</td>
                    <td>{$v.platform}</td>
                    <td>{$v.userAccount}</td>
                    <td>{$v.country}</td>
                    <td class="tr">{$v.sale_amount}</td>
                    <td class="tr">{$v.refund_amount}</td>
                    <td class="tr">{$v.promotion}</td>
                    <td class="tr">{$v.shipping_service}</td>
                    <td class="tr">{$v.liquidation}</td>
                    <td class="tr">{$v.adjustment}</td>
                    <td>{$v.created_at}</td>
                    <td class="tc">
                        <a href="{:url('order', ['id' => $v.id])}" class="layui-btn layui-btn layui-btn-sm">查看</a>
                        <a href="{:url('index_edit', ['id' => $v.id])}" class="layui-btn layui-btn-normal layui-btn-sm">编辑</a>
                        <button data-id="{$v.id}" class="layui-btn layui-btn-sm layui-btn-danger ml0" lay-submit lay-filter="Detele">删除</button>
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
            upload = layui.upload;

        // 导入
        let uploadInst = upload.render({
            elem: '#excel' //绑定元素
            ,url: '/Manage/upload/finance_order_upload' //上传接口
            ,exts: 'xls|xlsx|csv'
            ,data: {
                payment_type: function(){
                    return $("#payment_type").val();
                }
            }
            ,multiple: true
            ,before: function (obj){
                layer.load(1);
            }
            ,done: function(res){
                //上传完毕回调
                if (res.code === 1) {
                    location.href = "/Manage/Finance/import/rid/{$rid}/filename/" + encodeURIComponent(res.data) + "/origin/" + res.origin + "/payment_type/" + res.payment_type;
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
            layer.confirm('确定删除吗？',{icon:3,closeBtn:0,title:false,btnAlign:'c'},function(){
                $('button').attr('disabled',true);
                button.text('请稍候...');
                axios.post("{:url('table_delete')}", {id:id})
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

        // 修正
        form.on('submit(Edit)', function(data){
            var text = $(this).text(),
                button = $(this),
                id = {$report_id};
            layer.confirm('确认修正吗？请先确保销售金额已核对',{icon:3,closeBtn:0,title:false,btnAlign:'c'},function(){
                $('button').attr('disabled',true);
                button.text('请稍候...');
                axios.post("{:url('order_statistics_edit_auto')}", {id:id})
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
