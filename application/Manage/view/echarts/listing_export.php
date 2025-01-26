
{include file="public/header" /}

<!-- 主体内容 -->
<div class="layui-body" id="LAY_app_body">
    <div class="right">
        <a href="{:session('back_url', '', 'manage')}" class="layui-btn layui-btn-danger layui-btn-sm fr"><i class="layui-icon">&#xe603;</i>返回上一页</a>
        <div class="title">Listing导出</div>
		<div class="layui-form">
            <div class="layui-form-item">
                <label class="layui-form-label">品名</label>
                <div class="layui-input-inline w300">
                    <textarea name="local_name" id="local_name" placeholder="多个用回车键分割" class="layui-textarea"></textarea>
                </div>
            </div>
            <div class="layui-form-item">
                <label class="layui-form-label">仓库SKU</label>
                <div class="layui-input-inline w300">
                    <textarea name="local_sku" id="local_sku" placeholder="多个用回车键分割" class="layui-textarea"></textarea>
                </div>
            </div>
			<div class="layui-form-item">
                <div class="layui-input-block">
                    <button class="layui-btn layui-btn-normal w200" lay-submit lay-filter="Export"><i class="layui-icon">&#xe63c;</i> 导出</button>
                </div>
			</div>
		</div>
    </div>
</div>
<script src="https://unpkg.com/axios/dist/axios.min.js"></script>
<script>
layui.use(['form', 'jquery'], function(){
	let $ = layui.jquery,
		form = layui.form;

    //监听提交
    form.on('submit(Export)', function(data){
        let input1 = $("#local_name").val().trim();
        let result1 = input1.replace(/\r?\n/g, ',');
        let input2 = $("#local_sku").val().trim().toUpperCase();
        let result2 = input2.replace(/\r?\n/g, ',');
        location.href = "/Manage/Echarts/rank_export.html?local_name=" + result1 + "&local_sku=" + result2;
        return false;
    });
});
</script>

{include file="public/footer" /}
