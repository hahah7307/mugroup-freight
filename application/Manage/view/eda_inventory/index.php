
{include file="public/header" /}

<!-- 主体内容 -->
<div class="layui-body" id="LAY_app_body">
    <div class="right">
        <div class="title">易达云批次库存</div>
        <form class="layui-form search-form" method="get">
            <div class="layui-inline w200">
                <input type="text" class="layui-input" name="keyword" value="{$keyword}" placeholder="SKU/各种单号">
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
                    <col width="60">
                </colgroup>
                <thead>
                <tr>
                    <th>ID</th>
                    <th>userAgeId</th>
                    <th>skuCode</th>
                    <th>barcode</th>
                    <th>skuName</th>
                    <th>skuNameEn</th>
                    <th>weight</th>
                    <th>countryName</th>
                    <th>warehouseName</th>
                    <th>warehouseCode</th>
                    <th>businessNo</th>
                    <th>customerNo</th>
                    <th>totalStock</th>
                    <th class="red">stockAge</th>
                    <th>计费日期</th>
                    <th>合计</th>
                    <th>状态</th>
                </tr>
                </thead>
                <tbody>
                {foreach name="list" item="v"}
                <tr>
                    <td>{$v.id}</td>
                    <td>{$v.userAgeId}</td>
                    <td>{$v.skuCode}</td>
                    <td>{$v.barcode}</td>
                    <td>{$v.skuName}</td>
                    <td>{$v.skuNameEn}</td>
                    <td class="tr">{$v.weight}</td>
                    <td>{$v.countryName}</td>
                    <td>{$v.warehouseName}</td>
                    <td>{$v.warehouseCode}</td>
                    <td>{$v.businessNo}</td>
                    <td>{$v.customerNo}</td>
                    <td class="tr">{$v.totalStock}</td>
                    <td class="tr red">{$v.stockAge}</td>
                    <td class="tr">{$v.created_date}</td>
                    <td class="tr">{$v.price}</td>
                    <td>
                        {if condition="$v.is_finished eq 0"}
                            <p class="red">未核算</p>
                        {elseif condition="$v.is_finished eq 1"/}
                            <p class="grey">已核算</p>
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

    });
</script>

{include file="public/footer" /}
