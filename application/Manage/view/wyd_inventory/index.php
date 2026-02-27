
{include file="public/header" /}

<!-- 主体内容 -->
<div class="layui-body" id="LAY_app_body">
    <div class="right">
        <div class="title">无忧达批次库存</div>
        <form class="layui-form search-form" method="get">
            <div class="layui-inline w200">
                <input type="text" class="layui-input" name="keyword" value="{$keyword}" placeholder="SKU/各种单号">
            </div>
            <div class="layui-inline w120">
                <select name="warehouseCode" lay-verify="">
                    <option value="">仓库代码</option>
                    <option value="CAJW05" {if condition="$warehouseCode eq 'CAJW05'"}selected{/if}>CAJW05</option>
                    <option value="NJJW03" {if condition="$warehouseCode eq 'NJJW03'"}selected{/if}>NJJW03</option>
                </select>
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
            <button type="button" class="layui-btn  layui-btn-disabled" id="excel">导入</button>
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
                    <col>
                    <col>
                    <col>
                    <col>
                    <col width="60">
                </colgroup>
                <thead>
                <tr>
                    <th class="tc">
                        <input type="checkbox" lay-skin="primary" id="YanNanQiu_checkall" lay-filter="YanNanQiu_checkall">
                    </th>
                    <th>ID</th>
                    <th>englishName</th>
                    <th>masterSku</th>
                    <th>customerCode</th>
                    <th>pickUpTime</th>
                    <th>secondSku</th>
                    <th>inboundBatchNo</th>
                    <th class="red tr">storageAge</th>
                    <th>warehouseCode</th>
                    <th>volume</th>
                    <th>inventoryNum</th>
                    <th>inventoryAvailableNum</th>
                    <th>计费日期</th>
                    <th class="tc">合计</th>
                    <th>状态</th>
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
                    <td>{$v.id}</td>
                    <td>{$v.englishName}</td>
                    <td>{$v.masterSku}</td>
                    <td>{$v.customerCode}</td>
                    <td>{$v.pickUpTime}</td>
                    <td>{$v.secondSku}</td>
                    <td>{$v.inboundBatchNo}</td>
                    <td class="red tr">{$v.storageAge}</td>
                    <td>{$v.warehouseCode}</td>
                    <td class="tr">{$v.volume}</td>
                    <td class="tr">{$v.inventoryNum}</td>
                    <td class="tr">{$v.inventoryAvailableNum}</td>
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
