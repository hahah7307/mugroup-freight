
{include file="public/header" /}

<style>
    .sku-item {cursor: pointer}
    .layui-textarea {min-height: 0}
    thead {
        position: sticky;
        top: 0;
        z-index: 10;
        background: #fff;
    }
    .layui-form {margin-bottom: 10px}
</style>
<!-- 主体内容 -->
<div class="layui-body" id="LAY_app_body">
    <div class="right">
        <div class="title">Listing排名表</div>
        <form class="layui-form" method="get">
            <div class="layui-input-inline">
                <textarea name="local_name" id="local_name" placeholder="品名，多个用回车键分割" class="layui-textarea">{$local_name}</textarea>
            </div>
            <div class="layui-input-inline">
                <textarea name="local_sku" id="local_sku" placeholder="SKU，多个用回车键分割" class="layui-textarea">{$local_sku}</textarea>
            </div>
            <div class="layui-inline w200">
                <input type="text" class="layui-input" name="keyword" value="{$keyword}" placeholder="Asin/ParentAsin/销售SKU">
            </div>
            <div class="layui-inline w200">
                <input type="text" class="layui-input" name="nickname" value="{$nickname}" placeholder="主运营人员">
            </div>
            <div class="layui-inline w180">
                <select name="group_id" lay-verify="">
                    <option value="">请选择产品组</option>
                    {foreach name="listing_group" item="item"}
                    <option value="{$item.id}" {if condition="$group_id eq $item.id"}selected{/if}>{$item.group_name}</option>
                    {/foreach}
                </select>
            </div>
            <div class="layui-inline w120">
                <select name="order" lay-verify="">
                    <option value="DESC" {if condition="$order eq 'DESC'"}selected{/if}>从后到前</option>
                    <option value="ASC" {if condition="$order eq 'ASC'"}selected{/if}>从前到后</option>
                </select>
            </div>
            <div class="layui-input-inline w200">
                <input type="text" class="layui-input" id="start" name="start" value="{$start}" placeholder="开始时间">
            </div>
            <div class="layui-inline">
                <button class="layui-btn" lay-submit lay-filter="Search"><i class="layui-icon">&#xe615;</i> 查询</button>
            </div>
            <div class="layui-inline">
                <a href="{:url('Echarts/listing_export')}" class="layui-btn"><i class="layui-icon">&#xe63c;</i> 导出</a>
            </div>
        </form>

        <div class="layui-form table-flex">
            <table class="layui-table">
                <colgroup>
                    <col class="w80">
                    <col>
                    <col class="w200">
                    <col class="w200">
                    <col>
                    <col>
                    <col>
                    <col>
                    <col>
                    <col>
                    <col class="w120">
                </colgroup>
                <thead>
                <tr>
                    <th>名次</th>
                    <th>产品图片</th>
                    <th>Asin</th>
                    <th>Parent Asin</th>
                    <th>销售SKU</th>
                    <th>仓库SKU</th>
                    <th>品名</th>
                    <th>评分</th>
                    <th>小类排名</th>
                    <th>大类排名</th>
                    <th>主运营人员</th>
                </tr>
                </thead>
                <tbody>
                {foreach name="list" key="key" item="v"}
                <tr>
                    <td class="tr">{$key + 1}</td>
                    <td class="tc"><a href="{$v.small_image_url}" target="_blank"><img src="{$v.small_image_url}" height="50" alt=""></a></td>
                    <td>{$v.asin}</td>
                    <td>{$v.parent_asin}</td>
                    <td>{$v.seller_sku}</td>
                    <td>{$v.local_sku}</td>
                    <td>{$v.local_name}</td>
                    <td class="tr sku-item" data-asin="{$v.asin}">
                        <strong class="grey">{$v.last_star|round=###,1}</strong>
                        {if condition="round($v['last_star']) gt round($v['y_last_star'])"}
                        <i class="layui-icon iconfont icon-shangsheng"></i>
                        {elseif condition="round($v['last_star']) lt round($v['y_last_star'])"/}
                        <i class="layui-icon iconfont icon-xiajiang"></i>
                        {else/}
                        <i class="layui-icon iconfont icon-bhenggang"></i>
                        {/if}
                    </td>
                    <td class="tr sku-item" data-asin="{$v.asin}">
                        <strong class="grey">#{:number_format(json_decode($v['small_rank'], true)[0]['rank'])}</strong>
                        {if condition="json_decode($v['small_rank'], true)[0]['rank'] gt json_decode($v['y_small_rank'], true)[0]['rank']"}
                        <i class="layui-icon iconfont icon-xiajiang"></i>
                        {elseif condition="json_decode($v['small_rank'], true)[0]['rank'] lt json_decode($v['y_small_rank'], true)[0]['rank']"/}
                        <i class="layui-icon iconfont icon-shangsheng"></i>
                        {else/}
                        <i class="layui-icon iconfont icon-bhenggang"></i>
                        {/if}
                        <br>
                        {:json_decode($v['small_rank'], true)[0]['category']}
                    </td>
                    <td class="tr sku-item" data-asin="{$v.asin}">
                        <strong class="grey">#{:number_format($v['seller_rank'])}</strong>
                        {if condition="$v['seller_rank'] gt $v['y_seller_rank']"}
                        <i class="layui-icon iconfont icon-xiajiang"></i>
                        {elseif condition="$v['seller_rank'] lt $v['y_seller_rank']"/}
                        <i class="layui-icon iconfont icon-shangsheng"></i>
                        {else/}
                        <i class="layui-icon iconfont icon-bhenggang"></i>
                        {/if}
                        <br>
                        {$v.seller_category}
                    </td>
                    <td>{:json_decode($v['principal_info'], true)[0]['principal_name']}</td>
                </tr>
                {/foreach}
                </tbody>
            </table>
        </div>

    </div>
</div>
<script>
    layui.use(['form', 'jquery', 'laydate'], function(){
        var $ = layui.jquery,
            form = layui.form,
            laydate = layui.laydate;

        // 显示日期选择器
        laydate.render({
            elem: '#start',
            type: 'date'
        });

        $(".sku-item").click(function(){
            let sku = $(this).data('asin');
            location.href = "/Manage/Echarts/rank_change/asin/" + sku + ".html";
        });
    });
</script>

{include file="public/footer" /}
