
{include file="public/header" /}

<!-- 主体内容 -->
<div class="layui-body" id="LAY_app_body">
    <div class="right">
        <div class="title">易仓仓储费</div>
        <form class="layui-form search-form" method="get">
            <div class="layui-inline w200">
                <input type="text" class="layui-input" name="keyword" value="{$keyword}" placeholder="SKU/各种单号">
            </div>
            <div class="layui-inline w120">
                <select name="lc_code" lay-verify="">
                    <option value="">仓库代码</option>
                    <option value="LC-USLAX08" {if condition="$lc_code eq 'LC-USLAX08'"}selected{/if}>LC-USLAX08</option>
                    <option value="USLAX09" {if condition="$lc_code eq 'USLAX09'"}selected{/if}>USLAX09</option>
                    <option value="LC-USLAX05" {if condition="$lc_code eq 'LC-USLAX05'"}selected{/if}>LC-USLAX05</option>
                    <option value="LC-USNJ06" {if condition="$lc_code eq 'LC-USNJ06'"}selected{/if}>LC-USNJ06</option>
                    <option value="LC-USATL06" {if condition="$lc_code eq 'LC-USATL06'"}selected{/if}>LC-USATL06</option>
                    <option value="CAP2" {if condition="$lc_code eq 'CAP2'"}selected{/if}>CAP2</option>
                    <option value="LG-USA-PA01" {if condition="$lc_code eq 'LG-USA-PA01'"}selected{/if}>LG-USA-PA01</option>
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
                    <col width="200">
                    <col>
                    <col>
                    <col>
                    <col>
                    <col>
                    <col>
                    <col>
                    <col width="60">
                    <col width="140">
                    <col width="140">
                    <col width="100">
                    <col>
                    <col width="60">
                </colgroup>
                <thead>
                <tr>
                    <th class="tc">
                        <input type="checkbox" lay-skin="primary" id="YanNanQiu_checkall" lay-filter="YanNanQiu_checkall">
                    </th>
                    <th>ID</th>
                    <th>批次ID</th>
                    <th>SKU</th>
                    <th class="tc">仓库ID</th>
                    <th>仓库代码</th>
                    <th class="tc">可用数量</th>
                    <th class="tc">待出数量</th>
                    <th>参考单号</th>
                    <th>入库单号</th>
                    <th>采购单号</th>
                    <th class="red tc">库龄</th>
                    <th>上架时间</th>
                    <th>更新时间</th>
                    <th>抓取日期</th>
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
                    <td>{$v.ib_id}</td>
                    <td>{$v.productSku}</td>
                    <td class="tc">{$v.warehouseId}</td>
                    <td>{$v.lcCode}</td>
                    <td class="tc">{$v.ibQuantity}</td>
                    <td class="tc">{$v.outQuantity}</td>
                    <td>{$v.referenceNo}</td>
                    <td>{$v.roCode}</td>
                    <td>{$v.poCode}</td>
                    <td class="red tc">{$v.age}</td>
                    <td>{$v.fifoTime}</td>
                    <td>{$v.updateTime}</td>
                    <td>{$v.createdDate|strtotime|date="Y-m-d",###}</td>
                    <td>{$v.storageFee}</td>
                    <td>
                        {if condition="$v.is_settlement eq 0"}
                            <p class="red">未核算</p>
                        {elseif condition="$v.is_settlement eq 1"/}
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
