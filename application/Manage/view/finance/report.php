
{include file="public/header" /}

<style>
    .layui-btn {margin: 4px 0}
</style>

<!-- 主体内容 -->
<div class="layui-body" id="LAY_app_body">
    <div class="right">
        <div class="title">财务报表</div>
        <form class="layui-form search-form" method="get">
            <div class="layui-inline w200">
                <input type="text" class="layui-input" name="keyword" value="{$keyword}" placeholder="报表名称">
            </div>
            <div class="layui-inline">
                <button class="layui-btn" lay-submit lay-filter="Search"><i class="layui-icon">&#xe615;</i> 查询</button>
            </div>
            <div class="layui-inline">
                <a class="layui-btn layui-btn-normal" href="{:url('index')}"><i class="layui-icon">&#xe621;</i> 重置</a>
            </div>
        </form>

        <div class="layui-form">
            <a class="layui-btn" href="{:url('report_add')}">添加</a>
            <button class="layui-btn layui-btn-danger" id="introduction">SOP</button>
            <table class="layui-table">
                <colgroup>
                    <col width="50">
                    <col>
                    <col width="100">
                    <col width="80">
                    <col width="270">
                </colgroup>
                <thead>
                <tr>
                    <th>ID</th>
                    <th>报表名称</th>
                    <th>月份</th>
                    <th class="tc">状态</th>
                    <th class="tc">操作</th>
                </tr>
                </thead>
                <tbody>
                {foreach name="list" item="v"}
                <tr>
                    <td>{$v.id}</td>
                    <td>{$v.name}</td>
                    <td>{$v.month}</td>
                    <td class="tc">
                        {if condition="$v.is_notify eq 1"}
                            <span class="green">已同步</span>
                        {else/}
                            <span class="red">未同步</span>
                        {/if}
                    </td>
                    <td class="tl">
                        <a href="{:url('index', ['id' => $v.id])}" class="layui-btn layui-btn-sm">账单</a>
                        <a href="{:url('warehouse', ['id' => $v.id])}" class="layui-btn layui-btn-sm">仓租</a>
                        <a href="{:url('additional', ['id' => $v.id])}" class="layui-btn layui-btn-sm">额外</a>
                        <a href="{:url('evaluation', ['id' => $v.id])}" class="layui-btn layui-btn-sm">测评</a>
                        <a href="{:url('store', ['id' => $v.id])}" class="layui-btn layui-btn-sm">库存</a>
                        <a href="{:url('outbound', ['id' => $v.id])}" class="layui-btn layui-btn-sm">出库</a>
                        <a href="{:url('share', ['id' => $v.id])}" class="layui-btn layui-btn-sm">分摊</a>
                        <a href="{:url('report_edit', ['id' => $v.id])}" class="layui-btn layui-btn-normal layui-btn-sm">编辑</a>
                        <a href="{:url('report_export', ['id' => $v.id, 'month' => $v.month])}" class="layui-btn layui-btn-normal layui-btn-sm">导出</a>
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
        var $ = layui.jquery,
            form = layui.form;

        // 使用说明
        $("#introduction").click(function() {
            layer.alert('见到你真的很高兴', {
                title: false,
                area: ['1000px', '800px'],
                content:
"<b>财报制作和最终导出流程：</b><br/>" +
"1.在每个自然月10号之前优先导入当月的订单统计数据，如需要补充缺失数据请联系IT及时处理<br/>" +
"2.广告费：合计Amazon广告费，把本月广告费总数告知IT用于分摊，IT会根据广告费总额和分摊关系给出Amazon广告费和FBA仓储费的分摊表；非Amazon平台广告费需手动导入<br/>" +
"3.各平台账单导入，原则上一个表只用于一个平台，一个平台可以有多个表<br/>" +
"注意事项：<br/>" +
"①Amazon的15店US_YAATEE_US有点特别，账单少一列，需人工手动插入I列，数值不限<br/>" +
"②Wayfair平台账单为Inovice，退款和调整从账单中取，处理完格式后导入，具体格式查看参考文档<br/>" +
"4.在账单的Wayfair模块导入账单销售，用于核对账单的销售合计，数据会在最终导出表的某一个sheet中出现<br/>" +
"5.依次导入FBM仓储费（海外仓账单），四项额外费用（主要为促销费用），测评，月初库存表<br/>" +
"注意事项：<br/>" +
"<span class='red'>①月初库存表必须最后导入，导入后系统就会开始自动核算和分摊！！！同时清空库存表也会清空核算和分摊数据</span><br/>" +
"②仓储费需要把多个海外仓账单的仓租部分合并，具体格式查看参考文档<br/>" +
"③四项费用合计为Amazon平台专用部分，数据维度涉及店铺、仓库SKU和对应费用，具体格式查看参考文档<br/>" +
"6.财报状态为已同步时，方可导出，如果同步时间超过一个小时，请及时联系IT协助解决<br/>" +
"<br/>" +
"<b>常见问题及费用分摊逻辑：</b><br/>" +
"1.财报中各平台各店铺的销量、销售、税、佣金、FBA尾程数据皆来自订单统计。销售额能对上的情况下系统支持自动调整佣金及FBA尾程（操作修正后系统中订单统计的数据会同步成账单数据以确保分摊结果准确）<br/>" +
"2.某些挂账的订单数据如未出库、退货退款等情况不会出现在平台主表中，单独出现在账单未出库统计表中，核算账单时需要加上这些销售；欧洲站为不含税金额，核算时需要算上税<br/>" +
"3.财报中Amazon平台各店铺退款量、退款额、佣金退款、FBA尾程退款、退款其他、调整费用、清算费用、退运费数据，其他平台退款额、调整费用皆来自平台账单。只有销售SKU和仓库SKU映射关系完整的情况下可完全分摊<br/>" +
"4.Amazon平台促销费总额来自平台账单，操作人员需优先在导入账单后并核对运营的分摊总额准确无误后方可导入分摊结果。该费用分摊完全按运营分摊结果呈现<br/>" +
"5.财报中亚马逊平台广告费、FBA仓储费数据来自领星财务MSKU报表，同样只有销售SKU和仓库SKU映射关系完整的情况下可完全分摊<br/>" +
"6.财报中FBM仓储费只会分摊给账单列表中出现的各平台各店铺，本月有销售的仓库SKU仓储费会按销量分摊；无销售的仓库SKU会按内置逻辑优先挂靠给主产品（不存在或其他有疑问的主产品可手动调整），最终按该主产品有销售的店铺平均分摊<br/>" +
"7.财报中FBM尾程数据取根据海外仓报价单计算的理论尾程，如尾程数据有明显问题或者缺失的情况，请及时联系IT修正和补充<br/>" +
"8.财报中DDP数据会根据本月所有产品的实际出库时间和数量按照库存表先进先出的原则分配给每条出库数据",
                btnAlign: 'c',
                closeBtn: 0,
                anim: 1,
            });
        });

        // 状态
        form.on('switch(formLock)', function(data){
            $('button').attr('disabled',true);
            axios.post("{:url('status')}", {id:data.value,type:'look'})
                .then(function (response) {
                    var res = response.data;
                    if (res.code === 0) {
                        layer.alert(data.msg,{icon:2,closeBtn:0,title:false,btnAlign:'c'},function(){
                            location.reload();
                        });
                    }
                })
                .catch(function (error) {
                    console.log(error);
                });
            return false;
        });

        // 删除
        form.on('submit(Detele)', function(data){
            var text = $(this).text(),
                button = $(this),
                id = $(this).data('id');
            layer.confirm('确定删除吗？',{icon:3,closeBtn:0,title:false,btnAlign:'c'},function(){
                $('button').attr('disabled',true);
                button.text('请稍候...');
                axios.post("{:url('delete')}", {id:id})
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
