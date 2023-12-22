
{include file="public/header" /}

<!-- 主体内容 -->
<style>
    .layui-form-label {width: 144px!important;}
</style>
<div class="layui-body" id="LAY_app_body">
    <div class="right layui-form">
        <div class="title">输入默认值</div>
		<div>
            <div class="layui-form-item">
                <div class="layui-inline layui-col-md3">
                    <label class="layui-form-label">头程价格标准(元/CBM)</label>
                    <div class="layui-input-inline">
                        <input type="text" autocomplete="off" class="layui-input w300" name="flp_standard" value="{$config['flp_standard']}">
                    </div>
                </div>
                <div class="layui-inline layui-col-md3">
                    <label class="layui-form-label">派送方式</label>
                    <div class="layui-input-inline">
                        <input type="text" autocomplete="off" class="layui-input w300" name="delivery" value="{$config['delivery']}">
                    </div>
                </div>
            </div>
            <div class="layui-form-item">
                <div class="layui-inline layui-col-md3">
                    <label class="layui-form-label">关税率</label>
                    <div class="layui-input-inline">
                        <input type="text" autocomplete="off" class="layui-input w300" name="tariff_rate" value="{$config['tariff_rate']}">
                    </div>
                </div>
                <div class="layui-inline layui-col-md3">
                    <label class="layui-form-label">汇率</label>
                    <div class="layui-input-inline">
                        <input type="text" autocomplete="off" class="layui-input w300" name="exchange_rate" value="{$config['exchange_rate']}">
                    </div>
                </div>
            </div>
            <div class="layui-form-item">
                <div class="layui-inline layui-col-md3">
                    <label class="layui-form-label">广告费占比</label>
                    <div class="layui-input-inline">
                        <input type="text" autocomplete="off" class="layui-input w300" name="ad_rate" value="{$config['ad_rate']}">
                    </div>
                </div>
                <div class="layui-inline layui-col-md3">
                    <label class="layui-form-label">退货率</label>
                    <div class="layui-input-inline">
                        <input type="text" autocomplete="off" class="layui-input w300" name="return_rate" value="{$config['return_rate']}">
                    </div>
                </div>
                <div class="layui-inline layui-col-md3">
                    <label class="layui-form-label">平台费占比</label>
                    <div class="layui-input-inline">
                        <input type="text" autocomplete="off" class="layui-input w300" name="platform_rate" value="{$config['platform_rate']}">
                    </div>
                </div>
            </div>
		</div>

        <div class="title">基础默认值</div>
        <div>
            <div class="layui-form-item">
                <div class="layui-inline layui-col-md3">
                    <label class="layui-form-label">磅/千克</label>
                    <div class="layui-input-inline">
                        <input type="text" autocomplete="off" class="layui-input w300" name="kg_pound" value="{$config['kg_pound']}">
                    </div>
                </div>
                <div class="layui-inline layui-col-md3">
                    <label class="layui-form-label">装柜体积</label>
                    <div class="layui-input-inline">
                        <input type="text" autocomplete="off" class="layui-input w300" name="container_volume" value="{$config['container_volume']}">
                    </div>
                </div>
                <label class="layui-form-label">体积重比值(1/密度)</label>
                <div class="layui-input-inline">
                    <input type="text" autocomplete="off" class="layui-input w300" name="density_fraction" value="{$config['density_fraction']}">
                </div>
            </div>
        </div>

        <div class="title">比较默认值</div>
        <div>
            <div class="layui-form-item">
                <div class="layui-inline layui-col-md3">
                    <label class="layui-form-label">最长边最大值(cm)</label>
                    <div class="layui-input-inline">
                        <input type="text" autocomplete="off" class="layui-input w300" name="min_3leng" value="{$config['min_3leng']}">
                    </div>
                </div>
                <div class="layui-inline layui-col-md3">
                    <label class="layui-form-label">最低长+2*宽+2*高(cm)</label>
                    <div class="layui-input-inline">
                        <input type="text" autocomplete="off" class="layui-input w300" name="min_5leng" value="{$config['min_5leng']}">
                    </div>
                </div>
            </div>
        </div>
        <div class="layui-form-item">
            <div class="layui-input-block">
                <button class="layui-btn w200" lay-submit lay-filter="formCoding">提交保存</button>
            </div>
        </div>
    </div>
</div>
<script>
layui.use(['form', 'jquery'], function(){
	let $ = layui.jquery,
		form = layui.form;

	//监听提交
	form.on('submit(formCoding)', function(data){
		let text = $(this).text(),
			button = $(this);
		$('button').attr('disabled',true);
		button.text('请稍候...');
        axios.post("{:url('price')}", data.field)
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
