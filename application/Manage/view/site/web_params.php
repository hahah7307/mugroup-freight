
{include file="public/header" /}

<!-- 主体内容 -->
<div class="layui-body" id="LAY_app_body">
    <div class="right">
        <div class="title">网站设置</div>
		<div class="layui-form">
			<div class="layui-form-item">
				<label class="layui-form-label">Logo</label>
				<div class="layui-input-inline w300">
					<input type="text" class="layui-input" name="logo" style="display: none;">
					<span class="input-group-btn">
						<button type="button" name="logo" class="layui-btn layui-btn-primary layui-btn-sm YanNanQiu_ViewsUploader">选择图片</button>
						<ul class="YanNanQiu-upload-list">
						{if condition="$info.logo neq null"}
							<li style="margin: 2px">
								<img src="/upload/{$info.logo}">
								<span>
									<i class="fa fa-times"></i>
								</span>
								<input type="hidden" name="logo[]" value="{$info.logo}">
							</li>
						{/if}
						</ul>
					</span>
				</div>
				<label class="layui-form-label">Qrcode</label>
				<div class="layui-input-inline w300">
					<input type="text" class="layui-input" name="qrcode" style="display: none;">
					<span class="input-group-btn">
						<button type="button" name="qrcode" class="layui-btn layui-btn-primary layui-btn-sm YanNanQiu_ViewsUploader">选择图片</button>
						<ul class="YanNanQiu-upload-list">
						{if condition="$info.qrcode neq null"}
							<li style="margin: 2px">
								<img src="/upload/{$info.qrcode}">
								<span>
									<i class="fa fa-times"></i>
								</span>
								<input type="hidden" name="qrcode[]" value="{$info.qrcode}">
							</li>
						{/if}
						</ul>
					</span>
				</div>
			</div>
			<div class="layui-form-item">
				<label class="layui-form-label">网站名称</label>
				<div class="layui-input-inline w300">
					<input type="text" class="layui-input" name="web_title" value="{$info.web_title}" placeholder="请填写网站名称">
				</div>
				<label class="layui-form-label">公司名称</label>
				<div class="layui-input-inline w300">
					<input type="text" class="layui-input" name="corporate_name" value="{$info.corporate_name}" placeholder="请填写公司名称">
				</div>
			</div>
			<div class="layui-form-item">
				<label class="layui-form-label">备案号</label>
				<div class="layui-input-inline w300">
					<input type="text" class="layui-input" name="icp" value="{$info.icp}" placeholder="请填写备案号">
				</div>
				<label class="layui-form-label">版权信息</label>
				<div class="layui-input-inline w300">
					<input type="text" class="layui-input" name="copyrights" value="{$info.copyrights}" placeholder="请填写版权信息">
				</div>
			</div>
			<div class="layui-form-item">
				<label class="layui-form-label">邮箱</label>
				<div class="layui-input-inline w300">
					<input type="text" class="layui-input" name="email" value="{$info.email}" placeholder="请填写邮箱">
				</div>
				<label class="layui-form-label">电话</label>
				<div class="layui-input-inline w300">
					<input type="text" class="layui-input" name="telephone" value="{$info.telephone}" placeholder="请填写电话">
				</div>
			</div>
			<div class="layui-form-item">
				<label class="layui-form-label">传真</label>
				<div class="layui-input-inline w300">
					<input type="text" class="layui-input" name="fax" value="{$info.fax}" placeholder="请填写传真">
				</div>
			</div>
			<div class="layui-form-item">
				<label class="layui-form-label">地址</label>
				<div class="layui-input-block">
					<input type="text" class="layui-input" name="address" value="{$info.address}" placeholder="请填写地址">
				</div>
			</div>
			<div class="layui-form-item layui-form-text">
				<label class="layui-form-label">SEO标题</label>
				<div class="layui-input-block">
					<input type="text" class="layui-input" name="seo_title" value="{$info.seo_title}" placeholder="请填写SEO标题">
				</div>
			</div>
			<div class="layui-form-item layui-form-text">
				<label class="layui-form-label">SEO关键词</label>
				<div class="layui-input-block">
					<input type="text" class="layui-input" name="seo_keyword" value="{$info.seo_keyword}" placeholder="请填写SEO关键词">
				</div>
			</div>
			<div class="layui-form-item layui-form-text">
				<label class="layui-form-label">SEO描述</label>
				<div class="layui-input-block">
					<textarea class="layui-textarea" name="seo_description" placeholder="请填写SEO描述">{$info.seo_description}</textarea>
				</div>
			</div>
			<div class="layui-form-item layui-form-text">
				<label class="layui-form-label">统计代码</label>
				<div class="layui-input-block">
					<textarea class="layui-textarea" name="third_code_footer" placeholder="请填写统计代码">{$info.third_code_footer}</textarea>
				</div>
			</div>
			<div class="layui-form-item">
				<div class="layui-input-block">
					<input type="hidden" name="id" value="{$info.id}">
					<button class="layui-btn w200" lay-submit lay-filter="formCoding">提交保存</button>
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
			type:'POST',url:"{:url('web_params')}",data:data.field,dataType:'json',
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
