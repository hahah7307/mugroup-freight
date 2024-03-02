
{include file="public/header" /}

<!-- 主体内容 -->
<div class="layui-body" id="LAY_app_body">
    <div class="right">
		<a href="{:url('report')}" class="layui-btn layui-btn-danger layui-btn-sm fr"><i class="layui-icon">&#xe603;</i>返回上一页</a>
        <div class="title">编辑报表</div>
		<div class="layui-form">
			<div class="layui-form-item">
				<label class="layui-form-label">报表名称</label>
				<div class="layui-input-inline w300">
					<input type="text" class="layui-input" name="name" value="{$info.name}" placeholder="请填写报表名称">
				</div>
			</div>
            <div class="layui-form-item">
                <label class="layui-form-label">月份</label>
                <div class="layui-input-inline w300">
                    <input type="text" class="layui-input" id="month" name="month" value="{$info.month}" placeholder="请选择月份">
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

    // 显示日期选择器
    laydate.render({
        elem: '#month',
        type: 'month'
    });

	//监听提交
	form.on('submit(formCoding)', function(data){
		var text = $(this).text(),
			button = $(this);
		$('button').attr('disabled',true);
		button.text('请稍候...');
        axios.post("{:url('report_edit', ['id' => $info['id']])}", data.field)
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
