
{include file="public/header" /}

<!-- 主体内容 -->
<div class="layui-body" id="LAY_app_body">
    <div class="right">
		<a href="{:url('StorageOutbound/index')}" class="layui-btn layui-btn-danger layui-btn-sm fr"><i class="layui-icon">&#xe603;</i>返回上一页</a>
        <div class="title">修改出库费</div>
        <div class="layui-form">
            <div class="layui-form-item">
                <label class="layui-form-label">仓库ID</label>
                <div class="layui-input-inline w300">
                    <input type="text" class="layui-input" name="storage_id" value="{$info.storage_id}">
                </div>
            </div>
            <div class="layui-form-item">
                <label class="layui-form-label">平台</label>
                <div class="layui-input-inline w300">
                    <input type="text" class="layui-input" name="platform_tag" value="{$info.platform_tag}">
                </div>
            </div>
            <div class="layui-form-item">
                <label class="layui-form-label">名称</label>
                <div class="layui-input-inline w300">
                    <input type="text" class="layui-input" name="name" value="{$info.name}">
                </div>
            </div>
            <div class="layui-form-item">
                <label class="layui-form-label">描述</label>
                <div class="layui-input-inline w300">
                    <input type="text" class="layui-input" name="description" value="{$info.description}">
                </div>
            </div>
            <div class="layui-form-item">
                <label class="layui-form-label">短描述(选填)</label>
                <div class="layui-input-inline w300">
                    <input type="text" class="layui-input" name="short" value="{$info.short}">
                </div>
            </div>
            <div class="layui-form-item">
                <label class="layui-form-label">condition</label>
                <div class="layui-input-inline w300">
                    <textarea name="condition" class="layui-textarea">{$info.condition}</textarea>
                </div>
            </div>
            <div class="layui-form-item">
                <label class="layui-form-label">金额</label>
                <div class="layui-input-inline w300">
                    <input type="text" class="layui-input" name="value" value="{$info.value}">
                </div>
            </div>
            <div class="layui-form-item">
                <label class="layui-form-label">Level</label>
                <div class="layui-input-inline w300">
                    <input type="text" class="layui-input" name="level" value="{$info.level}">
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
</div>
<script src="https://unpkg.com/axios/dist/axios.min.js"></script>
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
        axios.post("{:url('edit', ['id' => $info['id']])}", data.field)
            .then(function (response) {
                var res = response.data;
                if (res.code === 1) {
                    layer.alert(res.msg,{icon:1,closeBtn:0,title:false,btnAlign:'c',},function(){
                        location.reload();
                    });
                } else {
                    layer.alert(res.msg,{icon:2,closeBtn:0,title:false,btnAlign:'c'},function(){
                        layer.closeAll();
                        $('button').attr('disabled',false);
                        button.text(text);
                    });
                }
            })
            .catch(function (error) {
                console.log(error);
            });
        return false;
	});
});
</script>

{include file="public/footer" /}
