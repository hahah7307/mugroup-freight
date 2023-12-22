<!doctype html>
<html>
<head>
	<meta charset="utf-8">
	<title>用户管理</title>
	<link rel="stylesheet" href="/static/layuiadmin/layui/css/layui.css" media="all">
	<link rel="stylesheet" href="/static/layuiadmin/style/admin.css" media="all">
	<script type="text/javascript" src="__PUBLIC__/layui/layui.js"></script>
</head>
<body>

<div class="container">

	<include file="Index:left"/>

	<div class="right">

		<div class="title">列表</div>

		<div class="layui-form">
			<div class="layui-form-item">
				<a class="layui-btn" href="">添加</a>
				<button class="layui-btn layui-btn-normal" lay-submit lay-filter="Delete">删除</button>
			</div>
			<table class="layui-table">
				<colgroup>
					<col class="w80">
					<col class="w80">
					<col>
					<col class="w150">
					<col class="w80">
					<col class="w150">
				</colgroup>
				<thead>
					<tr>
						<th class="tc"><input type="checkbox" lay-skin="primary" lay-filter="All"></th>
						<th class="tc">ID</th>
						<th class="tl">帐号</th>
						<th class="tc">权限组</th>
						<th class="tc">锁定</th>
						<th class="tc">操作</th>
					</tr>
				</thead>
				<tbody>
					<div>{$map}</div>
					<foreach name="account" item="v">
					<tr>
						<td class="tc">
							<input type="checkbox" class="box" name="" value="" lay-skin="primary">
						</td>
						<td class="tc">1</td>
						<td class="tl">{$v.username}</td>
						<td class="tc">
							<if condition="$v['super'] eq 1">
								<span class="orange">超级管理员</span>
							<else/>
								<foreach name="v.Role" item="vo">{$vo.nickname}</foreach>
							</if>
						</td>
						<td class="tc">
							<input type="checkbox" name="look" value="{$v.id}" lay-skin="switch" lay-text="是|否" lay-filter="formLock" <if condition="$v['look'] eq 1">checked</if>>
						</td>
						<td class="tc">
							<div class="layui-btn-group">
								<a href="" class="layui-btn layui-btn-sm">记录</a>
								<a href="" class="layui-btn layui-btn-sm layui-btn-normal">编辑</a>
							</div>
						</td>
					</tr>
					</foreach>
				</tbody>
			</table>
		</div>
	
	</div>

</div>


</body>
<script type="text/javascript">
layui.use(['jquery','layer','form'],function(){
	var $ = layui.jquery,
		layer = layui.layer,
		form = layui.form;

	// 选中
	form.on('checkbox(All)',function(data){
		data.elem.checked ? $('.box').prop('checked',true) : $('.box').prop('checked',false);
		form.render();
	});

	// 删除
	form.on('submit(Delete)', function(data){
		var text = $(this).text(), button = $(this);
		layer.confirm('确定要删除选中的{$tag}吗？',{icon:3,closeBtn:0,title:false,btnAlign:'c'},function(){
		$('button').attr('disabled',true);
		button.text('请稍候...');
			$.ajax({
				type:'POST',url:"",data:data.field,dataType:'json',
				success:function(data){
					if(data.status == 1){
						layer.alert(data.info,{icon:1,closeBtn:0,title:false,btnAlign:'c'},function(){
							location.reload();
						});
					}else{
						layer.alert(data.info,{icon:2,closeBtn:0,title:false,btnAlign:'c'},function(){
							layer.closeAll();
							$('button').attr('disabled',false);
							button.text(text);
						});
					}
				}
			});
		});
		return false;
	});

	// 状态
	form.on('switch(formLock)', function(data){
		$('button').attr('disabled',true);
		$.ajax({
			type:'POST',url:"",data:{id:data.value,type:'look'},dataType:'json',
			success:function(data){
				if(data.status == 0){
					layer.alert(data.info,{icon:2,closeBtn:0,title:false,btnAlign:'c'},function(){
						location.reload();
					});
				}
			}
		});
	});
});
</script>
</html>