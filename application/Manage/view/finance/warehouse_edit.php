
{include file="public/header" /}

<!-- 主体内容 -->
<div class="layui-body" id="LAY_app_body">
    <div class="right">
        <div class="title">修改主件</div>
		<div class="layui-form">
			<div class="layui-form-item">
				<label class="layui-form-label">原仓库Sku</label>
				<div class="layui-input-inline w300">
					<input type="text" class="layui-input" value="{$info.sku}" disabled>
				</div>
			</div>
            <div class="layui-form-item">
                <label class="layui-form-label">默认主件</label>
                <div class="layui-input-inline w300">
                    <input type="text" class="layui-input" value="{$info.main_sku}" disabled>
                </div>
            </div>
            <div class="layui-form-item">
                <label class="layui-form-label">新主件</label>
                <div class="layui-input-inline w300">
                    <input type="text" class="layui-input" name="new_sku" placeholder="请填写新的sku主件">
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
<script src="https://unpkg.com/axios/dist/axios.min.js"></script>
<script>
layui.use(['form', 'jquery', 'laydate'], function(){
	var $ = layui.jquery,
		form = layui.form,
        laydate = layui.laydate;

	//监听提交
	form.on('submit(formCoding)', function(data){
		var text = $(this).text(),
			button = $(this);
		$('button').attr('disabled',true);
		button.text('请稍候...');
        axios.post("{:url('warehouse_edit', ['id' => $info['id']])}", data.field)
            .then(function (response) {
                var res = response.data;
                if (res.code === 1) {
                    layer.alert(res.msg,{icon:1,closeBtn:0,title:false,btnAlign:'c',},function(){
                        location.href = "{:session('back_url', '', 'manage')}";
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
