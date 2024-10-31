
{include file="public/header" /}

<!-- 主体内容 -->
<div class="layui-body" id="LAY_app_body">
    <div class="right">
        <a href="{:session('back_url', '', 'manage')}" class="layui-btn layui-btn-danger layui-btn-sm fr"><i class="layui-icon">&#xe603;</i>返回上一页</a>
        <div class="title">出库明细列表</div>
        <form class="layui-form search-form" method="get">
            <div class="layui-inline w200">
                <input type="text" class="layui-input" name="keyword" value="{$keyword}" placeholder="参考/系统单号/仓库SKU">
            </div>
            <div class="layui-inline w100">
                <input type="text" class="layui-input" name="page_num" value="{$page_num}" placeholder="每页条数">
            </div>
            <div class="layui-inline">
                <button class="layui-btn" lay-submit lay-filter="Search"><i class="layui-icon">&#xe615;</i> 查询</button>
            </div>
        </form>

        <div class="layui-form">
            <button type="button" class="layui-btn  layui-btn-{if condition='$edit'}disabled{else/}normal{/if}" lay-submit lay-filter="Generate">Generate</button>
            <button data-id="{$report_id}" class="layui-btn layui-btn-danger ml0" lay-submit lay-filter="Detele">清空</button>
            <table class="layui-table" lay-size="sm">
                <colgroup>
                    <col width="80">
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
                    <col width="140">
                    <col>
                    <col>
                    <col width="80">
                </colgroup>
                <thead>
                <tr>
                    <th>探路者ID</th>
                    <th>账单ID</th>
                    <th>平台</th>
                    <th>店铺名</th>
                    <th>易仓发货推送号</th>
                    <th>Payment订单号</th>
                    <th>外销合同号</th>
                    <th>采购单号</th>
                    <th>店铺SKU</th>
                    <th>仓库SKU</th>
                    <th>发货方式</th>
                    <th>支付时间</th>
                    <th>发货时间</th>
                    <th>数量(个)</th>
                    <th>DDP(元)</th>
                    <th class="tc">是否核算</th>
                </tr>
                </thead>
                <tbody>
                {foreach name="list" item="v"}
                <tr>
                    <td class="tr">{$v.id}</td>
                    <td class="tr">{$v.table_id}</td>
                    <td>{$v.platform}</td>
                    <td>{$v.user_account}</td>
                    <td>{$v.saleOrderCode}</td>
                    <td>{$v.payment_id}</td>
                    <td>{$v.store.export_no}</td>
                    <td>{$v.store.content}</td>
                    <td>{$v.seller_sku}</td>
                    <td>{$v.warehouse_sku}</td>
                    <td>{$v.fulfillment}</td>
                    <td>{$v.paid_time}</td>
                    <td>{$v.shipping_time}</td>
                    <td class="tr">{$v.qty}</td>
                    <td class="tr">{$v.store.sku_ddp_unit * $v.qty}</td>
                    <td class="tc">
                        {if condition="$v.is_notify eq 0"}
                            <p class="blue">待核算</p>
                        {elseif condition="$v.is_notify eq 1" /}
                            <p class="green">已核算</p>
                        {elseif condition="$v.is_notify eq 2" /}
                            <p class="red">未通过</p>
                        {/if}
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
    layui.use(['form', 'jquery'], function(){
        let $ = layui.jquery,
            form = layui.form;

        // Generate
        form.on('submit(Generate)', function(data){
            var text = $(this).text(),
                button = $(this),
                id = {$report_id};
            layer.confirm('确认生成吗？请先确认已导入所有账单',{icon:3,closeBtn:0,title:false,btnAlign:'c'},function(){
                $('button').attr('disabled',true);
                button.text('请稍候...');
                axios.post("{:url('outbound_generate')}", {id:id})
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

        // 删除
        form.on('submit(Detele)', function(data){
            var text = $(this).text(),
                button = $(this),
                id = $(this).data('id');
            layer.confirm('确定出库明细列表吗？',{icon:3,closeBtn:0,title:false,btnAlign:'c'},function(){
                $('button').attr('disabled',true);
                button.text('请稍候...');
                axios.post("{:url('outbound_empty')}", {id:id})
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
