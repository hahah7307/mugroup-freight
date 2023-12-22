
{include file="public/header" /}

<!-- 主体内容 -->
<div class="layui-body" id="LAY_app_body">
    <div class="right">
        <div class="title">参数设置</div>
		<div class="layui-form">
			<div class="layui-form-item">
				<label class="layui-form-label">相关产品数</label>
				<div class="layui-input-inline w300">
					<input type="text" class="layui-input" name="aboutProduct" value="{$info.aboutProduct}" placeholder="请填写相关产品数">
				</div>
			</div>
			<div class="layui-form-item">
				<label class="layui-form-label">推荐产品数</label>
				<div class="layui-input-inline w300">
					<input type="text" class="layui-input" name="hotProduct" value="{$info.hotProduct}" placeholder="请填写推荐产品数">
				</div>
			</div>
			<div class="layui-form-item">
				<label class="layui-form-label">首页产品数</label>
				<div class="layui-input-inline w300">
					<input type="text" class="layui-input" name="indexProduct" value="{$info.indexProduct}" placeholder="请填写产品列表数">
				</div>
			</div>
			<div class="layui-form-item">
				<label class="layui-form-label">最新产品数</label>
				<div class="layui-input-inline w300">
					<input type="text" class="layui-input" name="lastProduct" value="{$info.lastProduct}" placeholder="请填写最新产品数">
				</div>
			</div>
			<div class="layui-form-item">
				<label class="layui-form-label">产品列表数</label>
				<div class="layui-input-inline w300">
					<input type="text" class="layui-input" name="listProduct" value="{$info.listProduct}" placeholder="请填写产品列表数">
				</div>
			</div>
			<div class="layui-form-item">
				<label class="layui-form-label">文章列表数</label>
				<div class="layui-input-inline w300">
					<input type="text" class="layui-input" name="listNews" value="{$info.listNews}" placeholder="请填写文章列表数">
				</div>
			</div>
			<div class="layui-form-item">
				<label class="layui-form-label">最新文章数</label>
				<div class="layui-input-inline w300">
					<input type="text" class="layui-input" name="lastArticle" value="{$info.lastArticle}" placeholder="请填写最新文章数">
				</div>
			</div>
			<div class="layui-form-item">
				<label class="layui-form-label">最近单页数</label>
				<div class="layui-input-inline w300">
					<input type="text" class="layui-input" name="lastPage" value="{$info.lastPage}" placeholder="请填写最近单页数">
				</div>
			</div>
			<div class="layui-form-item">
				<label class="layui-form-label">下载列表数</label>
				<div class="layui-input-inline w300">
					<input type="text" class="layui-input" name="listDown" value="{$info.listDown}" placeholder="请填写下载列表数">
				</div>
			</div>
			<div class="layui-form-item">
				<div class="layui-input-block">
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
			type:'POST',url:"{:url('web')}",data:data.field,dataType:'json',
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
