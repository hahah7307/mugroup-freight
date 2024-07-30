
{include file="public/header" /}

<!-- 主体内容 -->
<div class="layui-body" id="LAY_app_body">
    <div class="right">
        <div class="title">修改主件</div>
		<div class="layui-form">
            <div class="layui-form-item">
                <label class="layui-form-label">主销售平台</label>
                <div class="layui-input-inline w300">
                    <input type="text" class="layui-input" value="{$info.main_platform}" disabled>
                </div>
            </div>
			<div class="layui-form-item">
				<label class="layui-form-label">原仓库Sku</label>
				<div class="layui-input-inline w300">
					<input type="text" class="layui-input" value="{$info.sku}" disabled>
				</div>
			</div>
            <div class="layui-form-item">
                <label class="layui-form-label">主件SKU</label>
                <div class="layui-input-inline w300">
                    <input type="text" class="layui-input" name="main_sku" value="{$info.main_sku}">
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
        axios.post("{:url('warehouse_fbm_edit', ['id' => $info['id']])}", data.field)
            .then(function (response) {
                var res = response.data;
                if (res.code === 1) {
                    layer.alert(res.msg,{icon:1,closeBtn:0,title:false,btnAlign:'c',},function(){
                        location.href = "{:url('warehouse_fbm', ['id' => $info['report_id']])}";
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
