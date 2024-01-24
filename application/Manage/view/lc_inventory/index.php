
{include file="public/header" /}

<!-- 主体内容 -->
<div class="layui-body" id="LAY_app_body">
    <div class="right">
        <div class="title">良仓仓储费</div>
        <form class="layui-form search-form" method="get">
            <div class="layui-inline w200">
                <input type="text" class="layui-input" name="keyword" value="{$keyword}" placeholder="SKU/各种单号">
            </div>
            <div class="layui-inline w120">
                <select name="warehouse_code" lay-verify="">
                    <option value="">仓库代码</option>
                    <option value="USNJ06" {if condition="$warehouse_code eq 'USNJ06'"}selected{/if}>USNJ06</option>
                    <option value="USLAX08" {if condition="$warehouse_code eq 'USLAX08'"}selected{/if}>USLAX08</option>
                    <option value="USLAX09" {if condition="$warehouse_code eq 'USLAX09'"}selected{/if}>USLAX09</option>
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
                    <th>入库单号</th>
                    <th>仓库代码</th>
                    <th>入库时间</th>
                    <th class="tc">可用数量</th>
                    <th class="tc">待出数量</th>
                    <th class="red tc">库龄</th>
                    <th>抓取日期</th>
                    <th>体积</th>
                    <th>合计</th>
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
                    <td>{$v.receiving_code}</td>
                    <td>{$v.warehouse_code}</td>
                    <td>{$v.receiving.receiving_add_time}</td>
                    <td class="tc">{$v.sellable_quantity}</td>
                    <td class="tc">{$v.reserved_quantity}</td>
                    <td class="red tc">{$v.stock_age}</td>
                    <td>{$v.created_date}</td>
                    <td>{$v.volume}</td>
                    <td>{$v.price}</td>
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
