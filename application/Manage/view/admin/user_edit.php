
{include file="public/header" /}

<!-- 主体内容 -->
<div class="layui-body" id="LAY_app_body">
    <div class="right">
		<a href="{:session('manage.back_url')}" class="layui-btn layui-btn-danger layui-btn-sm fr"><i class="layui-icon">&#xe603;</i>返回上一页</a>
        <div class="title">编辑管理员</div>
		<div class="layui-form">
			<div class="layui-form-item">
				<label class="layui-form-label">用户名</label>
				<div class="layui-input-inline w300">
					<input type="text" class="layui-input" name="username" value="{$info.username}" placeholder="请填写用户名">
				</div>
			</div>
			<div class="layui-form-item">
				<label class="layui-form-label">用户昵称</label>
				<div class="layui-input-inline w300">
					<input type="text" class="layui-input" name="nickname" value="{$info.nickname}" placeholder="请填写用户昵称">
				</div>
			</div>
			<div class="layui-form-item">
				<label class="layui-form-label">用户角色</label>
				<div class="layui-input-inline w300">
					<input type="hidden" name="role">
					<div id="role" class="xm-select-demo"></div>
				</div>
			</div>
			<div class="layui-form-item">
				<label class="layui-form-label">手机号码</label>
				<div class="layui-input-inline w300">
					<input type="text" class="layui-input" name="phone" value="{$info.phone}" placeholder="请填写手机号码">
				</div>
			</div>
			<div class="layui-form-item">
				<label class="layui-form-label">邮箱</label>
				<div class="layui-input-inline w300">
					<input type="text" class="layui-input" name="email" value="{$info.email}" placeholder="请填写邮箱">
				</div>
			</div>
			<div class="layui-form-item">
				<label class="layui-form-label">密码</label>
				<div class="layui-input-inline w300">
					<input type="text" class="layui-input" name="password" placeholder="请填写密码">
				</div>
			</div>
			<div class="layui-form-item">
				<label class="layui-form-label">重复密码</label>
				<div class="layui-input-inline w300">
					<input type="text" class="layui-input" name="repassword" placeholder="请填写重复密码">
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
layui.use(['form', 'jquery'], function(){
	var $ = layui.jquery,
		form = layui.form;

    // 下拉框多选
    var cid = xmSelect.render({
        el: '#role',
        name: 'role',
        layVerify: 'required',
        filterable: true,
        remoteSearch: true,
        remoteMethod: function(val, cb, show){
            axios({
                method: 'post',
                url: '/Manage/Check/get_full_adminRole',
                data:{cid: "{$info.role}", keyword: val},
                // params: {
                //     keyword: val,
                // }
            }).then(response => {
                var res = response.data;
                cb(res.data)
            }).catch(err => {
                cb([]);
            });
        },
        data: []
    })

	//监听提交
	form.on('submit(formCoding)', function(data){
		var text = $(this).text(),
			button = $(this);
		$('button').attr('disabled',true);
		button.text('请稍候...');
		$.ajax({
			type:'POST',url:"{:url('user_edit', ['id' => $info['id']])}",data:data.field,dataType:'json',
			success:function(data){
				if(data.code == 1){
					layer.alert(data.msg,{icon:1,closeBtn:0,title:false,btnAlign:'c'},function(){
						location.href = "{:url('index')}";
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
