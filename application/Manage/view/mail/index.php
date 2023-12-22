
{include file="public/header" /}

<!-- 主体内容 -->
<div class="layui-body" id="LAY_app_body">
    <div class="right">
        <div class="title">邮件设置</div>
		<div class="layui-form">
			<div class="layui-form-item">
				<label class="layui-form-label">发件人邮箱</label>
				<div class="layui-input-inline w300">
					<input type="text" class="layui-input" name="senderEmail" value="{$info.senderEmail}" placeholder="请填写发件人邮箱">
				</div>
			</div>
			<div class="layui-form-item">
				<label class="layui-form-label">发件人名称</label>
				<div class="layui-input-inline w300">
					<input type="text" class="layui-input" name="senderName" value="{$info.senderName}" placeholder="请填写发件人名称">
				</div>
			</div>
			<div class="layui-form-item">
				<label class="layui-form-label">SMTP地址</label>
				<div class="layui-input-inline w300">
					<input type="text" class="layui-input" name="smtpAddress" value="{$info.smtpAddress}" placeholder="请填写SMTP地址">
				</div>
			</div>
			<div class="layui-form-item">
				<label class="layui-form-label">SMTP端口</label>
				<div class="layui-input-inline w300">
					<input type="text" class="layui-input" name="smtpPort" value="{$info.smtpPort}" placeholder="请填写SMTP端口">
				</div>
			</div>
			<div class="layui-form-item">
				<label class="layui-form-label">邮箱账号</label>
				<div class="layui-input-inline w300">
					<input type="text" class="layui-input" name="emailAccount" value="{$info.emailAccount}" placeholder="请填写邮箱账号">
				</div>
			</div>
			<div class="layui-form-item">
				<label class="layui-form-label">邮箱密码</label>
				<div class="layui-input-inline w300">
					<input type="text" class="layui-input" name="emailPassword" value="{$info.emailPassword}" placeholder="请填写邮箱密码">
				</div>
			</div>
			<div class="layui-form-item">
				<label class="layui-form-label">接受地址</label>
				<div class="layui-input-inline w300">
					<input type="text" class="layui-input" name="acceptEmail" value="{$info.acceptEmail}" placeholder="请填写接受邮箱地址">
				</div>
			</div>
			<div class="layui-form-item">
				<div class="layui-input-block">
					<input type="hidden" name="id" value="{$info.id}">
					<button class="layui-btn w200" lay-submit lay-filter="formCoding">提交保存</button>
				</div>
			</div>
			<div class="layui-form-item">
				<div class="layui-input-block">
					<button class="layui-btn layui-btn-warm w200" lay-submit lay-filter="formSend">发送测试</button>
				</div>
			</div>
		</div>
    </div>
</div>
<script src="/static/manage/js/uploader-use.js"></script>
<script>
layui.use(['form', 'jquery'], function(){
	var $ = layui.jquery,
		form = layui.form;

	//监听提交
	form.on('submit(formCoding)', function(data){
		var text = $(this).text(),
			button = $(this);
		$('button').attr('disabled',true);
		button.text('请稍候...');
		$.ajax({
			type:'POST',url:"{:url('index')}",data:data.field,dataType:'json',
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

	//监听提交
	form.on('submit(formSend)', function(data){
		var text = $(this).text(),
			button = $(this);
		$('button').attr('disabled',true);
		button.text('请稍候...');
		$.ajax({
			type:'POST',url:"{:url('send')}",data:{},dataType:'json',
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
