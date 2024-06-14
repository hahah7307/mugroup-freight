
{include file="public/header" /}

<!-- 主体内容 -->
<div class="layui-body" id="LAY_app_body">
    <div class="right">
		<a href="{:session('back_url', '', 'manage')}" class="layui-btn layui-btn-danger layui-btn-sm fr"><i class="layui-icon">&#xe603;</i>返回上一页</a>
        <div class="title">修正订单统计数据</div>
		<div class="layui-form">
			<div class="layui-form-item">
				<label class="layui-form-label">PAYMENT</label>
				<div class="layui-input-inline w300">
					<input type="text" class="layui-input" name="payment_id" value="{$info.payment_id}" placeholder="请填写PAYMENT">
				</div>
			</div>
            <div class="layui-form-item">
                <label class="layui-form-label">参考号</label>
                <div class="layui-input-inline w300">
                    <input type="text" class="layui-input" name="saleOrderCode" value="{$info.saleOrderCode}" placeholder="请填写参考号">
                </div>
            </div>
            <div class="layui-form-item">
                <label class="layui-form-label">销售SKU</label>
                <div class="layui-input-inline w300">
                    <input type="text" class="layui-input" name="seller_sku" value="{$info.seller_sku}" placeholder="请填写销售SKU">
                </div>
            </div>
            <div class="layui-form-item">
                <label class="layui-form-label">仓库SKU</label>
                <div class="layui-input-inline w300">
                    <input type="text" class="layui-input" name="platform_sku" value="{$info.platform_sku}" placeholder="请填写仓库SKU">
                </div>
            </div>
            <div class="layui-form-item">
                <label class="layui-form-label">数量</label>
                <div class="layui-input-inline w300">
                    <input type="text" class="layui-input" name="qty" value="{$info.qty}" placeholder="请填写数量">
                </div>
            </div>
            <div class="layui-form-item">
                <label class="layui-form-label">单价</label>
                <div class="layui-input-inline w300">
                    <input type="text" class="layui-input" name="sale_unit" value="{$info.sale_unit}" placeholder="请填写单价">
                </div>
            </div>
            <div class="layui-form-item">
                <label class="layui-form-label">总销售额</label>
                <div class="layui-input-inline w300">
                    <input type="text" class="layui-input" name="sale_amount" value="{$info.sale_amount}" placeholder="请填写总销售额">
                </div>
            </div>
            <div class="layui-form-item">
                <label class="layui-form-label">运费</label>
                <div class="layui-input-inline w300">
                    <input type="text" class="layui-input" name="sale_shipping" value="{$info.sale_shipping}" placeholder="请填写运费">
                </div>
            </div>
            <div class="layui-form-item">
                <label class="layui-form-label">佣金</label>
                <div class="layui-input-inline w300">
                    <input type="text" class="layui-input" name="selling_fee" value="{$info.selling_fee}" placeholder="请填写佣金">
                </div>
            </div>
            <div class="layui-form-item">
                <label class="layui-form-label">FBA尾程</label>
                <div class="layui-input-inline w300">
                    <input type="text" class="layui-input" name="fba_fee" value="{$info.fba_fee}" placeholder="请填写FBA尾程">
                </div>
            </div>
            <div class="layui-form-item">
                <label class="layui-form-label">税</label>
                <div class="layui-input-inline w300">
                    <input type="text" class="layui-input" name="tax" value="{$info.tax}" placeholder="请填写税">
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
        axios.post("{:url('order_statistics_edit', ['id' => $info['id']])}", data.field)
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
