
{include file="public/header" /}

<!-- 主体内容 -->
<div class="layui-body" id="LAY_app_body">
    <div class="right">
        <div class="title">每日库存结算</div>
        <form class="layui-form search-form" method="get">
            <div class="layui-inline w200">
                <input type="text" class="layui-input" name="keyword" value="{$keyword}" placeholder="SKU">
            </div>
            <div class="layui-inline w200">
                <input type="text" class="layui-input" name="date" value="{$date}" placeholder="结算日期">
            </div>
            <div class="layui-inline w120">
                <select name="warehouse_id" lay-verify="">
                    <option value="">仓库代码</option>
                    <option value="24" {if condition="$warehouse_id eq '24'"}selected{/if}>CAP2</option>
                    <option value="26" {if condition="$warehouse_id eq '26'"}selected{/if}>CAP4</option>
                    <option value="28" {if condition="$warehouse_id eq '28'"}selected{/if}>LC-USATL06</option>
                    <option value="29" {if condition="$warehouse_id eq '29'"}selected{/if}>LC-USLAX08</option>
                    <option value="32" {if condition="$warehouse_id eq '32'"}selected{/if}>USLAX09</option>
                    <option value="33" {if condition="$warehouse_id eq '33'"}selected{/if}>LG-TN</option>
                    <option value="34" {if condition="$warehouse_id eq '34'"}selected{/if}>LC-USLAX05</option>
                    <option value="36" {if condition="$warehouse_id eq '36'"}selected{/if}>LC-USNJ06</option>
                    <option value="37" {if condition="$warehouse_id eq '37'"}selected{/if}>LG-USA-PA01</option>
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
                    <col width="60">
                </colgroup>
                <thead>
                <tr>
                    <th class="tc">
                        <input type="checkbox" lay-skin="primary" id="YanNanQiu_checkall" lay-filter="YanNanQiu_checkall">
                    </th>
                    <th>ID</th>
                    <th>SKU</th>
                    <th>仓库代码</th>
                    <th class="tc">总入库数量</th>
                    <th class="tc">销售数量</th>
                    <th class="tc">理论库存</th>
                    <th class="tc">海外仓库存</th>
                    <th>抓取日期</th>
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
                    <td>{$v.product_sku}</td>
                    <td>
                        {if condition="$v.warehouse_id eq 24"}
                        <p>CAP2</p>
                        {elseif condition="$v.warehouse_id eq 26"/}
                        <p>CAP4</p>
                        {elseif condition="$v.warehouse_id eq 28"/}
                        <p>LC-USATL06</p>
                        {elseif condition="$v.warehouse_id eq 29"/}
                        <p>LC-USLAX08</p>
                        {elseif condition="$v.warehouse_id eq 32"/}
                        <p>USLAX09</p>
                        {elseif condition="$v.warehouse_id eq 33"/}
                        <p>LG-TN</p>
                        {elseif condition="$v.warehouse_id eq 34"/}
                        <p>LC-USLAX05</p>
                        {elseif condition="$v.warehouse_id eq 36"/}
                        <p>LC-USNJ06</p>
                        {elseif condition="$v.warehouse_id eq 37"/}
                        <p>LG-USA-PA01</p>
                        {/if}
                    </td>
                    <td class="tc">{$v.receiving.quantity_sum}</td>
                    <td class="tc">{$v.consume.quantity_sum}</td>
                    <td class="tc">{$v.stock}</td>
                    <td class="tc">{$v.storage_stock}</td>
                    <td>{$v.date}</td>
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
