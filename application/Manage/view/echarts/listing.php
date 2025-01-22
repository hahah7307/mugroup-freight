
{include file="public/header" /}

<style>
    .sku-item {cursor: pointer}
</style>
<!-- 主体内容 -->
<div class="layui-body" id="LAY_app_body">
    <div class="right">
        <div class="title">Listing排名表</div>
        <form class="layui-form" method="get">
            <div class="layui-inline w200">
                <input type="text" class="layui-input" name="keyword" value="{$keyword}" placeholder="Asin/ParentAsin/销售SKU">
            </div>
            <div class="layui-inline w120">
                <select name="order" lay-verify="">
                    <option value="DESC" {if condition="$order eq 'DESC'"}selected{/if}>从高到低</option>
                    <option value="ASC" {if condition="$order eq 'ASC'"}selected{/if}>从低到高</option>
                </select>
            </div>
            <div class="layui-input-inline w200">
                <input type="text" class="layui-input" id="start" name="start" value="{$start}" placeholder="开始时间">
            </div>
            <div class="layui-inline">
                <button class="layui-btn" lay-submit lay-filter="Search"><i class="layui-icon">&#xe615;</i> 查询</button>
            </div>
        </form>

        <div class="layui-form table-flex">
            <table class="layui-table">
                <colgroup>
                    <col class="w80">
                    <col>
                    <col>
                    <col>
                    <col>
                    <col>
                    <col>
                    <col>
                    <col>
                    <col class="w100">
                </colgroup>
                <thead>
                <tr>
                    <th>名次</th>
                    <th>产品图片</th>
                    <th>Asin</th>
                    <th>ParentAsin</th>
                    <th>销售SKU</th>
                    <th>品名</th>
                    <th>评分</th>
                    <th>小类排名</th>
                    <th>大类排名</th>
                    <th>运营人员</th>
                </tr>
                </thead>
                <tbody>
                {foreach name="list" key="key" item="v"}
                <tr>
                    <td class="tr">{$key + 1}</td>
                    <td class="tc sku-item" data-asin="{$v.asin}"><img src="{$v.small_image_url}" height="80" alt=""></td>
                    <td>{$v.asin}</td>
                    <td>{$v.parent_asin}</td>
                    <td>{$v.seller_sku}</td>
                    <td>{$v.local_name}</td>
                    <td class="tr"><strong class="red fs16">{$v.last_star|round=###,1}</strong></td>
                    <td class="tr">
                        <strong class="green">#{:number_format(json_decode($v['small_rank'], true)[0]['rank'])}</strong><br>
                        {:json_decode($v['small_rank'], true)[0]['category']}
                    </td>
                    <td class="tr">
                        <strong class="green">#{:number_format($v['seller_rank'])}</strong><br>
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
