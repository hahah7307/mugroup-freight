
{include file="public/header" /}

<style>

</style>
<!-- 主体内容 -->
<div class="layui-body" id="LAY_app_body">
    <div class="right">
        <div class="title">产品动销报表</div>
        <form class="layui-form" method="get">
            <div class="layui-inline w200">
                <input type="text" class="layui-input" name="keyword" value="{$keyword}" placeholder="SKU/品名/运营(全名)">
            </div>
            <div class="layui-input-inline w120">
                <input type="text" class="layui-input" id="date" name="date" value="{$date}" placeholder="日期">
            </div>
            <div class="layui-inline w150">
                <select name="turnover_order" lay-verify="">
                    <option value="DESC" {if condition="$turnover_order eq 'DESC'"}selected{/if}>年周转率倒序</option>
                    <option value="ASC" {if condition="$turnover_order eq 'ASC'"}selected{/if}>年周转率顺序</option>
                </select>
            </div>
            <div class="layui-input-inline w180">
                <div id="group_name"></div>
            </div>
            <div class="layui-inline">
                <button class="layui-btn" lay-submit lay-filter="Search"><i class="layui-icon">&#xe615;</i> 查询</button>
            </div>
        </form>

        <div class="layui-form table-flex">
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
                </colgroup>
                <thead>
                <tr>
                    <th>名次</th>
                    <th>仓库Sku</th>
                    <th>品名</th>
                    <th>产品图片</th>
                    <th>二级类目</th>
                    <th>一级类目</th>
                    <th>主销售</th>
                    <th>年周转率</th>
                </tr>
                </thead>
                <tbody>
                {foreach name="list" key="key" item="v"}
                <tr class="sku-item" data-sku="{$v.warehouseSku}">
                    <td class="tr">{$key + 1}</td>
                    <td>{$v.productSku}</td>
                    <td>{$v.productTitle}</td>
                    <td><img src="{$v.productImages}" height="80" alt=""></td>
                    <td>{$v.group_name_origin}</td>
                    <td>{$v.group_name}</td>
                    <td>{$v.user_name}</td>
                    <td class="tr">{$v.profit|number_format=###, 3}</td>
                </tr>
                {/foreach}
                </tbody>
            </table>
        </div>

    </div>
</div>
<script>
    layui.use(['form', 'jquery', 'laydate'], function(){
        const $ = layui.jquery,
            form = layui.form,
            xmSelect = layui.xmSelect,
            laydate = layui.laydate;

        // 显示日期选择器
        laydate.render({
            elem: '#date',
            type: 'date'
        });

        // 启用搜索
        // var xmSelect = layui.xmSelect;
        // 确保 layui.use 已加载 xmSelect
        let select = xmSelect.render({
            el: '#group_name',
            name: 'group_name',
            radio: true,
            toolbar: {show: true},
            clickClose: true,
            filterable: true,
            remoteSearch: true,   // 开启远程搜索
            initValue: ['1'],
            remoteMethod: function(val, cb) {
                // val 是输入值
                // cb 是回调函数，用于返回数据
                $.get("{:url('Check/get_group_name_origin')}", {search: val, selected: ["{$group_name}"]}, function(res){
                    // cb(res.data); // 返回数组 [{name:'苹果',value:'1'}, ...]
                    let result = JSON.parse(res).filter(function(item){
                        return item.name.indexOf(val) !== -1;
                    });
                    cb(result);
                });
            }
        });
    });
</script>

{include file="public/footer" /}
