
{include file="public/header" /}

<style>
    .total {padding: 0 10px}
</style>
<!-- 主体内容 -->
<div class="layui-body" id="LAY_app_body">
    <div class="right">
        <a href="{:url('Storage/index')}" class="layui-btn layui-btn-danger layui-btn-sm fr"><i class="layui-icon">&#xe603;</i>返回上一页</a>
        <div class="title">SKU费用表</div>
        <form class="layui-form search-form" method="get">
            <div class="layui-inline w200">
                <input type="text" class="layui-input" name="keyword" value="{$keyword}" placeholder="">
            </div>
            <div class="layui-inline w120">
                <select name="warehouse_code" lay-verify="">
                    <option value="">所有仓点</option>
                    {foreach name="warehouse_area" item="area"}
                    <option value="{$area.storage_code}" {if condition="$area.storage_code eq $warehouse_code"}selected{/if}>{$area.storage_code}</option>
                    {/foreach}
                </select>
            </div>
            <div class="layui-inline w120">
                <select name="is_peak" lay-verify="">
                    <option value="-1">是否旺季</option>
                    <option value="1" {if condition="$is_peak eq 1"}selected{/if}>含旺季</option>
                    <option value="0" {if condition="$is_peak eq 0"}selected{/if}>不含旺季</option>
                </select>
            </div>
            <div class="layui-inline">
                <button class="layui-btn" lay-submit lay-filter="Search"><i class="layui-icon">&#xe615;</i> 查询</button>
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
                    <col>
                    <col>
                    <col>
                    <col>
                    <col>
                    <col>
                </colgroup>
                <thead>
                <tr>
                    <th>SKU</th>
                    <th>仓点</th>
                    <th>是否含旺季</th>
                    <th>毛重</th>
                    <th>长</th>
                    <th>宽</th>
                    <th>高</th>
                    <th>实重</th>
                    <th>体积重</th>
                    <th>尺寸AHS</th>
                    <th>重量AHS</th>
                    <th>是否OS</th>
                    <th>头程</th>
                    <th>卸柜费</th>
                    <th>基础运费</th>
                    <th>出库费</th>
                    <th>仓储费</th>
                    <th>合计</th>
<!--                    <th class="tc">操作</th>-->
                </tr>
                </thead>
                <tbody>
                {foreach name="list" item="v"}
                <tr>
                    <td>{$v.product_sku}</td>
                    <td>{$v.warehouse_code}</td>
                    <td class="tr">{if condition="$v.is_peak eq 1"}是{else/}否{/if}</td>
                    <td class="tr">{$v.weight}</td>
                    <td class="tr">{$v.length}</td>
                    <td class="tr">{$v.width}</td>
                    <td class="tr">{$v.height}</td>
                    <td class="tr">{$v.gross_weight}</td>
                    <td class="tr">{$v.volume_weight}</td>
                    <td class="tr">{if condition="$v.is_ahs_d eq 1"}<span class="red">是</span>{else/}<span class="green">否</span>{/if}</td>
                    <td class="tr">{if condition="$v.is_ahs_w eq 1"}<span class="red">是</span>{else/}<span class="green">否</span>{/if}</td>
                    <td class="tr">{if condition="$v.is_os eq 1"}<span class="red">是</span>{else/}<span class="green">否</span>{/if}</td>
                    <td class="tr">{$v.first_leg_fee|number_format=###, 6, ".", ""}</td>
                    <td class="tr">{$v.devanning_fee|number_format=###, 6, ".", ""}</td>
                    <td class="tr">{$v.base_freight_fee|number_format=###, 2, ".", ""}</td>
                    <td class="tr">{$v.outbound_fee|number_format=###, 2, ".", ""}</td>
                    <td class="tr">{$v.warehouse_rent_fee|number_format=###, 6, ".", ""}</td>
                    <td class="tr">{$v.cost_total|number_format=###, 6, ".", ""}</td>
<!--                    <td class="tc">-->
<!--                        <a href="{:url('area_diff_item', ['id' => $v.id])}" class="layui-btn layui-btn-sm">查看产品</a>-->
<!--                    </td>-->
                </tr>
                {/foreach}
                </tbody>
            </table>
            {$list->render()}
        </div>

    </div>
</div>
<script>
    layui.use(['form', 'upload', 'jquery'], function(){
        let $ = layui.jquery,
            upload = layui.upload,
            form = layui.form;

        // 上传
        let uploadInst = upload.render({
            elem: '#excel' //绑定元素
            ,url: '/Manage/upload/file_upload' //上传接口
            ,exts: 'xls|xlsx|csv'
            ,multiple: true
            ,before: function (obj){
                layer.load(1);
            }
            ,done: function(res){
                //上传完毕回调
                console.log(res);
                if (res.code === 1) {
                    location.href = "/Manage/WarehouseShipment/area_diff_import/filename/" + res.data + "/origin/" + res.origin;
                } else {
                    layer.alert(res.msg,{icon:2,closeBtn:0,title:false,btnAlign:'c'},function(){
                        layer.closeAll();
                    });
                }
            }
            ,error: function(){
                //请求异常回调
            }
        });
    });
</script>

{include file="public/footer" /}
