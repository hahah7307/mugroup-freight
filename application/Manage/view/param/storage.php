
{include file="public/header" /}

<!-- 主体内容 -->
<style>
    .layui-form-label {width: 144px!important;}
</style>
<div class="layui-body" id="LAY_app_body">
    <div class="right layui-form">
        <div class="title">配置中心</div>
        <div>
            <div class="layui-form-item">
                <div class="layui-inline layui-col-md3">
                    <label class="layui-form-label">偏远附加费</label>
                    <div class="layui-input-inline">
                        <input type="text" class="layui-input w300" name="das_fee" value="{$config['das_fee']}">
                    </div>
                </div>
            </div>
        </div>
        <div>
            <div class="layui-form-item">
                <div class="layui-inline layui-col-md3">
                    <label class="layui-form-label">超偏远附加费</label>
                    <div class="layui-input-inline">
                        <input type="text" class="layui-input w300" name="dase_fee" value="{$config['dase_fee']}">
                    </div>
                </div>
            </div>
        </div>
        <div>
            <div class="layui-form-item">
                <div class="layui-inline layui-col-md3">
                    <label class="layui-form-label">超级偏远附加费</label>
                    <div class="layui-input-inline">
                        <input type="text" class="layui-input w300" name="dasr_fee" value="{$config['dasr_fee']}">
                    </div>
                </div>
            </div>
        </div>
		<div>
            <div class="layui-form-item">
                <div class="layui-inline layui-col-md3">
                    <label class="layui-form-label">AHS旺季附加费时间</label>
                    <div class="layui-input-inline">
                        <input type="text" class="layui-input w300" id="ahs_additional_time" name="ahs_additional_time" value="{$config['ahs_additional_time']}" placeholder="开始时间">
                    </div>
                </div>
            </div>
		</div>
        <div>
            <div class="layui-form-item">
                <div class="layui-inline layui-col-md3">
                    <label class="layui-form-label"></label>
                    <div class="layui-input-inline">
                        <input type="text" class="layui-input w300" id="ahs_additional_time2" name="ahs_additional_time2" value="{$config['ahs_additional_time2']}" placeholder="结束时间">
                    </div>
                </div>
            </div>
        </div>
        <div>
            <div class="layui-form-item">
                <div class="layui-inline layui-col-md3">
                    <label class="layui-form-label">住宅旺季附加费时间</label>
                    <div class="layui-input-inline">
                        <input type="text" class="layui-input w300" id="home_additional_time" name="home_additional_time" value="{$config['home_additional_time']}" placeholder="开始时间">
                    </div>
                </div>
            </div>
        </div>
        <div>
            <div class="layui-form-item">
                <div class="layui-inline layui-col-md3">
                    <label class="layui-form-label"></label>
                    <div class="layui-input-inline">
                        <input type="text" class="layui-input w300" id="home_additional_time2" name="home_additional_time2" value="{$config['home_additional_time2']}" placeholder="结束时间">
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
    laydate.render({
        elem: '#ahs_additional_time2',
        type: 'datetime'
    });

    laydate.render({
        elem: '#home_additional_time',
        type: 'datetime'
    });
    laydate.render({
        elem: '#home_additional_time2',
        type: 'datetime'
    });

	//监听提交
	form.on('submit(formCoding)', function(data){
		let text = $(this).text(),
			button = $(this);
		$('button').attr('disabled',true);
		button.text('请稍候...');
        axios.post("{:url('storage')}", data.field)
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
