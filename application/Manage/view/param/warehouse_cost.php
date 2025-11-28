
{include file="public/header" /}

<!-- 主体内容 -->
<style>
    .layui-form-label {width: 144px!important;}
</style>
<div class="layui-body" id="LAY_app_body">
    <div class="right layui-form">
        <div class="title">头程费用（尾程运费）</div>
        <div>
            <div class="layui-form-item">
                <div class="layui-inline layui-col-md5">
                    <label class="layui-form-label">CAP2</label>
                    <div class="layui-input-inline">
                        <input type="text" class="layui-input w300" name="CAP2" value="{$config['CAP2']}">
                    </div>
                </div>
            </div>
        </div>
        <div>
            <div class="layui-form-item">
                <div class="layui-inline layui-col-md5">
                    <label class="layui-form-label">LG-USA-PA01</label>
                    <div class="layui-input-inline">
                        <input type="text" class="layui-input w300" name="LG-USA-PA01" value="{$config['LG-USA-PA01']}">
                    </div>
                </div>
            </div>
        </div>
        <div>
            <div class="layui-form-item">
                <div class="layui-inline layui-col-md5">
                    <label class="layui-form-label">SAV</label>
                    <div class="layui-input-inline">
                        <input type="text" class="layui-input w300" name="SAV" value="{$config['SAV']}">
                    </div>
                </div>
            </div>
        </div>
        <div>
            <div class="layui-form-item">
                <div class="layui-inline layui-col-md5">
                    <label class="layui-form-label">HOU03</label>
                    <div class="layui-input-inline">
                        <input type="text" class="layui-input w300" name="HOU03" value="{$config['HOU03']}">
                    </div>
                </div>
            </div>
        </div>
        <div>
            <div class="layui-form-item">
                <div class="layui-inline layui-col-md5">
                    <label class="layui-form-label">LOCTEKOMS_HOU07</label>
                    <div class="layui-input-inline">
                        <input type="text" class="layui-input w300" name="LOCTEKOMS_HOU07" value="{$config['LOCTEKOMS_HOU07']}">
                    </div>
                </div>
            </div>
        </div>
        <div>
            <div class="layui-form-item">
                <div class="layui-inline layui-col-md5">
                    <label class="layui-form-label">LECANGS_HOU05</label>
                    <div class="layui-input-inline">
                        <input type="text" class="layui-input w300" name="LECANGS_HOU05" value="{$config['LECANGS_HOU05']}">
                    </div>
                </div>
            </div>
        </div>
        <div>
            <div class="layui-form-item">
                <div class="layui-inline layui-col-md5">
                    <label class="layui-form-label">LOCTEKOMS_NJF02</label>
                    <div class="layui-input-inline">
                        <input type="text" class="layui-input w300" name="LOCTEKOMS_NJF02" value="{$config['LOCTEKOMS_NJF02']}">
                    </div>
                </div>
            </div>
        </div>
        <div>
            <div class="layui-form-item">
                <div class="layui-inline layui-col-md5">
                    <label class="layui-form-label">WUYOUDA_CAJW04</label>
                    <div class="layui-input-inline">
                        <input type="text" class="layui-input w300" name="WUYOUDA_CAJW04" value="{$config['WUYOUDA_CAJW04']}">
                    </div>
                </div>
            </div>
        </div>
        <div>
            <div class="layui-form-item">
                <div class="layui-inline layui-col-md5">
                    <label class="layui-form-label">WUYOUDA_NJJW03</label>
                    <div class="layui-input-inline">
                        <input type="text" class="layui-input w300" name="WUYOUDA_NJJW03" value="{$config['WUYOUDA_NJJW03']}">
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
layui.use(['form', 'jquery', 'laydate'], function(){
	let $ = layui.jquery,
		form = layui.form,
        laydate = layui.laydate;

    // 显示日期选择器
    laydate.render({
        elem: '#ahs_additional_time',
        type: 'datetime'
    });

	//监听提交
	form.on('submit(formCoding)', function(data){
		let text = $(this).text(),
			button = $(this);
		$('button').attr('disabled',true);
		button.text('请稍候...');
        axios.post("{:url('warehouse_cost')}", data.field)
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
