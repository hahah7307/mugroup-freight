
{include file="public/header" /}

<!-- 主体内容 -->
<div class="layui-body" id="LAY_app_body">
    <div class="right">
        <div class="title">尾程试算列表</div>
        <form class="layui-form search-form" method="get">
            <div class="layui-inline w200">
                <input type="text" class="layui-input" name="keyword" value="{$keyword}" placeholder="Zone">
            </div>
            <div class="layui-inline">
                <button class="layui-btn" lay-submit lay-filter="Search"><i class="layui-icon">&#xe615;</i> 查询</button>
            </div>
        </form>

        <div class="layui-form">
            <a class="layui-btn" href="{:url('cost_estimate_eda_add')}">添加</a>
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
                    <col class="w300">
                </colgroup>
                <thead>
                <tr>
                    <th>id</th>
                    <th>warehouseId</th>
                    <th>warehouseCode</th>
                    <th>endCountryCode</th>
                    <th>city</th>
                    <th>endZipcode</th>
                    <th>manualInputSwitch</th>
                    <th>estimatedGoodsType</th>
                    <th>skuInfoDetails</th>
                    <th>packageDetails</th>
                    <th>created_time</th>
                    <th>successItems</th>
                </tr>
                </thead>
                <tbody>
                {foreach name="list" item="v"}
                <tr>
                    <td class="tr">{$v.id}</td>
                    <td>{$v.warehouseId}</td>
                    <td>{$v.warehouseCode}</td>
                    <td>{$v.endCountryCode}</td>
                    <td>{$v.city}</td>
                    <td>{$v.endZipcode}</td>
                    <td>{$v.manualInputSwitch}</td>
                    <td>{$v.estimatedGoodsType}</td>
                    <td>{:json_decode($v['skuInfoDetails'], true)['skuCode']} * {:json_decode($v['skuInfoDetails'], true)['quantity']}</td>
                    <td>{:json_decode($v['packageDetails'], true)['width']} * {:json_decode($v['packageDetails'], true)['height']} * {:json_decode($v['packageDetails'], true)['length']} * {:json_decode($v['packageDetails'], true)['weight']}</td>
                    <td class="tr">{$v.created_time}</td>
                    <td>
                        <table>
                        {foreach name="$v.successItems|json_decode=###,true", item="vi"}
                            <tbody {if condition="$vi.estimatedPrice elt 10"}class="green"{/if}>
                                <td>{$vi.logisticProductName}</td>
                                <td>{$vi.estimatedPrice}</td>
                                <td>{$vi.currencyCode}</td>
                            </tbody>
                        {/foreach}
                        </table>
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

    });
</script>

{include file="public/footer" /}
