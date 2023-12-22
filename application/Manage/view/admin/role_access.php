
{include file="public/header" /}

<!-- 主体内容 -->
<div class="layui-body" id="LAY_app_body">
    <div class="right">
		<a href="{:session('manage.back_url')}" class="layui-btn layui-btn-danger layui-btn-sm fr"><i class="layui-icon">&#xe603;</i>返回上一页</a>
        <div class="title">配置角色权限</div>
		<div class="layui-form">
			{foreach name="list" item="v"}
				<div class="access">
					<div class="access-style" style="padding: 20px;color: #333;background: #F3F3F3;text-align: center;font-weight: bold;margin-bottom: 10px;">
						<input type="checkbox" name="access[$v.id]" value="{$v.id}_1" level="1" lay-skin="primary" title="{$v.name}" lay-filter="one" {if condition="$v['access']"}checked{/if}>
					</div>
					<div class="clear"></div>
					{foreach name="v.child" item="vo"}
						<div class="access-action">
							<div class="access-title">
								<input type="checkbox" id="{$vo.id}" name="access[{$vo.id}]" value="{$vo.id}_2" level="2" lay-skin="primary" title="{$vo.name}" lay-filter="two" {if condition="$vo['access']"}checked{/if}>
							</div>
							<div class="clear"></div>
							{foreach name="vo.child" item="voo"}
								<div class="access-list mt10 mb10 ml20">
									<input type="checkbox" id="{$voo.id}" name="access[{$voo.id}]" value="{$voo.id}_3" level="3" lay-skin="primary" title="{$voo.name}" lay-filter="three" {if condition="$voo['access']"}checked{/if}>
								</div>
							{/foreach}
							<div class="clear"></div>
						</div>
					{/foreach}
				</div>
				<div class="clear"></div>
			{/foreach}
			<div class="layui-form-item">
				<div class="layui-input-block">
					<button class="layui-btn w200" lay-submit lay-filter="formCoding">提交保存</button>
				</div>
			</div>
		</div>
    </div>
</div>
<script>
layui.use(['form', 'jquery'], function(){
	var $ = layui.jquery,
		form = layui.form;

	form.on('checkbox(one)',function(data){
		var one = $(this).parents('.access').find('input');
		data.elem.checked ? one.prop('checked',true) : one.prop('checked',false);
		form.render();
	});

	form.on('checkbox(two)',function(data){
		var two = $(this).parents('.access-action').find('input');
		var levelone = $(this).parents('.access').find('input[level=1]');
		data.elem.checked ? two.prop('checked',true) : two.prop('checked',false);
		data.elem.checked ? levelone.prop('checked',true) : levelone.prop('checked',true);
		form.render();
	});

	form.on('checkbox(three)',function(data){
		var levelone = $(this).parents('.access-action').find('input[level=2]');
		var leveltwo = $(this).parents('.access').find('input[level=1]');
		data.elem.checked ? levelone.prop('checked',true) : levelone.prop('checked',true);
		data.elem.checked ? leveltwo.prop('checked',true) : leveltwo.prop('checked',true);
		form.render();
	});

	// 提交
	form.on('submit(formCoding)', function(data){
		var text = $(this).text(),
			button = $(this);
		$('button').attr('disabled',true);
		button.text('请稍候...');
		$.ajax({
			type:'POST',url:"{:url('role_access', ['id' => $role['id']])}",data:data.field,dataType:'json',
			success:function(data){
				if(data.code == 1){
					layer.alert(data.msg,{icon:1,closeBtn:0,title:false,btnAlign:'c'},function(){
						location.reload();
					});
				}else{
					layer.alert(data.msg,{icon:2,closeBtn:0,title:false,btnAlign:'c'},function(){
						layer.closeAll();
						$('button').attr('disabled',false);
						button.text(text);
					});
				}
			}
		});
		return false;
	});
});
</script>

{include file="public/footer" /}
