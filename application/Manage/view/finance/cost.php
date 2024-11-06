
{include file="public/header" /}

<style>
    .total {padding: 0 10px}
</style>
<!-- 主体内容 -->
<div class="layui-body" id="LAY_app_body">
    <div class="right">
        <a href="{:url('report')}" class="layui-btn layui-btn-danger layui-btn-sm fr"><i class="layui-icon">&#xe603;</i>返回上一页</a>
        <div class="title">核算费用列表</div>

        <div class="layui-form">
            <table class="layui-table" lay-size="sm">
                <colgroup>
                    <col width="120">
                    <col>
                    <col width="80">
                </colgroup>
                <thead>
                <tr>
                    <th>核算费用类</th>
                    <th>核算费用描述</th>
                    <th class="tc">操作</th>
                </tr>
                </thead>
                <tbody>
                <tr>
                    <td>仓租</td>
                    <td>海外仓仓储费（FBM仓储费、WFS仓储费，需导入，有分摊记录）</td>
                    <td class="tc">
                        <a href="{:url('warehouse', ['id' => $id])}" class="layui-btn layui-btn layui-btn-sm">查看</a>
                    </td>
                </tr>
                <tr>
                    <td>额外</td>
                    <td>额外费用（包含良仓调整费用、乐歌调整费用、WFS调整费用等，需导入，有分摊记录）</td>
                    <td class="tc">
                        <a href="{:url('additional', ['id' => $id])}" class="layui-btn layui-btn layui-btn-sm">查看</a>
                    </td>
                </tr>
                <tr>
                    <td>测评</td>
                    <td>测评费用（系统会根据实际出库进行分摊，且币种换算成美金只在最终导出表格内呈现，需导入）</td>
                    <td class="tc">
                        <a href="{:url('evaluation', ['id' => $id])}" class="layui-btn layui-btn layui-btn-sm">查看</a>
                    </td>
                </tr>
                <tr>
                    <td>广告</td>
                    <td>其他平台广告费（此处特指其他平台广告费，亚马逊平台广告费由领星提供系统自动分摊且无分摊数据留存，需导入）</td>
                    <td class="tc">
                        <a href="{:url('ad_cost', ['id' => $id])}" class="layui-btn layui-btn layui-btn-sm">查看</a>
                    </td>
                </tr>
                <tr>
                    <td>台账-国内广告</td>
                    <td>国内广告费（包含专利费、设计费、建模费、等其他费用，有分摊记录）</td>
                    <td class="tc">
                        <a href="{:url('operation_expenses', ['id' => $id])}" class="layui-btn layui-btn layui-btn-sm">查看</a>
                    </td>
                </tr>
                <tr>
                    <td>台账-工厂运费</td>
                    <td>工厂运费（包含工厂运费，有分摊记录）</td>
                    <td class="tc">
                        <a href="{:url('operation_factory', ['id' => $id])}" class="layui-btn layui-btn layui-btn-sm">查看</a>
                    </td>
                </tr>
                <tr>
                    <td>台账-国内快递</td>
                    <td>国内快递费（有分摊记录）</td>
                    <td class="tc">
                        <a href="{:url('operation_delivery', ['id' => $id])}" class="layui-btn layui-btn layui-btn-sm">查看</a>
                    </td>
                </tr>
                <tr>
                    <td>台账-工厂索赔</td>
                    <td>工厂索赔（包含工厂索赔）</td>
                    <td class="tc">
                        <a href="{:url('operation_factory_claim', ['id' => $id])}" class="layui-btn layui-btn layui-btn-sm">查看</a>
                    </td>
                </tr>
                <tr>
                    <td>WFS</td>
                    <td>WFS其他费用明细（包含WFS尾程、WFS退运费等）</td>
                    <td class="tc">
                        <a href="{:url('wfs_fulfillment', ['id' => $id])}" class="layui-btn layui-btn layui-btn-sm">查看</a>
                    </td>
                </tr>
                <tr>
                    <td>其他</td>
                    <td>从领星接口抓取按月分摊的MSKU明细（包含亚马逊广告费、促销费、清算费、FBA仓储费等，无分摊记录）</td>
                    <td class="tc">
                        <a href="{:url('ak_ad_cost', ['id' => $id])}" class="layui-btn layui-btn layui-btn-sm">查看</a>
                    </td>
                </tr>
                </tbody>
            </table>
        </div>

    </div>
</div>
<script>
    layui.use(['form', 'jquery'], function(){
        let $ = layui.jquery,
            form = layui.form;
    });
</script>

{include file="public/footer" /}
