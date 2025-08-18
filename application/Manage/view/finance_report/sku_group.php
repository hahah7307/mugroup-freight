
{include file="public/header" /}

<!-- 主体内容 -->
<div class="layui-body" id="LAY_app_body">
    <div class="right">
        <a href="{:session('back_url', '', 'manage')}" class="layui-btn layui-btn-danger layui-btn-sm fr"><i class="layui-icon">&#xe603;</i>返回上一页</a>
        <div class="title">产品分组列表</div>

		<div class="layui-form">
			<table class="layui-table" lay-size="sm">
				<colgroup>
					<col class="w80">
					<col>
					<col>
					<col>
				</colgroup>
				<thead>
					<tr>
						<th class="tc">ID</th>
						<th class="tl">SKU</th>
						<th class="tl">中文品名</th>
						<th class="tl">分组类别</th>
					</tr>
				</thead>
				<tbody>
					{foreach name="list" item="v"}
						<tr>
							<td class="tc">{$v.id}</td>
							<td class="tl">{$v.sku}</td>
							<td class="tl">{$v.product_name}</td>
							<td class="tl">{$v.group_name}</td>
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
