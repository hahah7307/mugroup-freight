
{include file="public/header" /}

<!-- 主体内容 -->
<div class="layui-body" id="LAY_app_body">
    <div class="right">
		<a href="{:session('back_url', '', 'manage')}" class="layui-btn layui-btn-danger layui-btn-sm fr"><i class="layui-icon">&#xe603;</i>返回上一页</a>
        <div class="title">编辑订单表</div>
		<div class="layui-form">
			<div class="layui-form-item">
				<label class="layui-form-label">报表名称</label>
				<div class="layui-input-inline w300">
					<input type="text" class="layui-input" name="table_name" value="{$info.table_name}" placeholder="请填写报表名称">
				</div>
			</div>
            <div class="layui-form-item">
                <label class="layui-form-label">所属平台</label>
                <div class="layui-input-inline w300">
                    <input type="text" class="layui-input" name="platform" value="{$info.platform}" placeholder="请填写所属平台">
                </div>
            </div>
            <div class="layui-form-item">
                <label class="layui-form-label">所属店铺</label>
                <div class="layui-input-inline w300">
                    <input type="text" class="layui-input" name="userAccount" value="{$info.userAccount}" placeholder="请填写所属店铺">
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
		form = layui.form;

	//监听提交
	form.on('submit(formCoding)', function(data){
		var text = $(this).text(),
			button = $(this);
		$('button').attr('disabled',true);
		button.text('请稍候...');
        axios.post("{:url('index_edit', ['id' => $info['id']])}", data.field)
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
