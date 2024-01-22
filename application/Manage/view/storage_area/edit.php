
{include file="public/header" /}

<!-- 主体内容 -->
<div class="layui-body" id="LAY_app_body">
    <div class="right">
		<a href="{:url('StorageArea/index')}" class="layui-btn layui-btn-danger layui-btn-sm fr"><i class="layui-icon">&#xe603;</i>返回上一页</a>
        <div class="title">子仓库编辑</div>
		<div class="layui-form">
            <div class="layui-form-item">
                <label class="layui-form-label">所属仓库</label>
                <div class="layui-input-inline w300">
                    <select name="storage_id" lay-verify="">
                        {foreach name="storage" item="v"}
                        <option value="{$v.id}" {if condition="$info.storage_id eq $v.id"}selected{/if}>{$v.name}</option>
                        {/foreach}
                    </select>
                </div>
            </div>
            <div class="layui-form-item">
                <label class="layui-form-label">子仓库位置</label>
                <div class="layui-input-inline w300">
                    <select name="type" lay-verify="">
                        <option value="1" {if condition="$info.type eq 1"}selected{/if}>美西</option>
                        <option value="2" {if condition="$info.type eq 2"}selected{/if}>美东</option>
                    </select>
                </div>
            </div>
            <div class="layui-form-item">
                <label class="layui-form-label">子仓库名称</label>
                <div class="layui-input-inline w300">
                    <input type="text" class="layui-input" name="name" value="{$info.name}">
                </div>
            </div>
            <div class="layui-form-item">
                <label class="layui-form-label">子仓库代码</label>
                <div class="layui-input-inline w300">
                    <input type="text" class="layui-input" name="storage_code" value="{$info.storage_code}">
                </div>
            </div>
            <div class="layui-form-item">
                <label class="layui-form-label">子仓库ID</label>
                <div class="layui-input-inline w300">
                    <input type="text" class="layui-input" name="warehouseId" value="{$info.warehouseId}">
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
