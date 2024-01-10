
{include file="public/header" /}

<!-- 主体内容 -->
<div class="layui-body" id="LAY_app_body">
    <div class="right">
        <div class="title">批次库存调整记录</div>
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
            <div class="layui-inline w120">
                <select name="applicationCode" lay-verify="">
                    <option value="">操作</option>
                    <option value="SO" {if condition="$applicationCode eq 'SO'"}selected{/if}>SO</option>
                    <option value="SSPI" {if condition="$applicationCode eq 'SSPI'"}selected{/if}>SSPI</option>
                    <option value="EO" {if condition="$applicationCode eq 'EO'"}selected{/if}>EO</option>
                    <option value="OSAPI" {if condition="$applicationCode eq 'OSAPI'"}selected{/if}>OSAPI</option>
                    <option value="Putaway" {if condition="$applicationCode eq 'Putaway'"}selected{/if}>Putaway</option>
                    <option value="TSO" {if condition="$applicationCode eq 'TSO'"}selected{/if}>TSO</option>
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
                    <col width="200">
                    <col>
                    <col>
                    <col>
                    <col>
                    <col>
                    <col>
                    <co>
                    <col width="140">
                </colgroup>
                <thead>
                <tr>
                    <th class="tc">
                        <input type="checkbox" lay-skin="primary" id="YanNanQiu_checkall" lay-filter="YanNanQiu_checkall">
                    </th>
                    <th>ID</th>
                    <th>SKU</th>
                    <th class="tc">仓库ID</th>
                    <th>仓库代码</th>
                    <th class="tc">操作代码</th>
                    <th>操作单号</th>
                    <th>入库单号</th>
                    <th>数量（前）</th>
                    <th>数量（后）</th>
                    <th>操作日期</th>
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
                    <td>{$v.productSku}</td>
                    <td class="tc">{$v.warehouseId}</td>
                    <td>{$v.lcCode}</td>
                    <td>{$v.applicationCode}</td>
                    <td>{$v.refNo}</td>
                    <td>{$v.roCode}</td>
                    <td>{$v.quantityBefore}</td>
                    <td>{$v.quantityAfter}</td>
                    <td>{$v.time}</td>
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
