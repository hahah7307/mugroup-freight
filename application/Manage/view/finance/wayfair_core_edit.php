

{include file="public/header" /}

<!-- 主体内容 -->
<div class="layui-body" id="LAY_app_body">
    <div class="right">
		<a href="{:session('back_url', '', 'manage')}" class="layui-btn layui-btn-danger layui-btn-sm fr"><i class="layui-icon">&#xe603;</i>返回上一页</a>
        <div class="title">修正订单统计数据</div>
		<div class="layui-form">
			<div class="layui-form-item">
				<label class="layui-form-label">订单号</label>
				<div class="layui-input-inline w300">
					<input type="text" class="layui-input" name="payment_id" value="{$info.payment_id}" placeholder="请填写订单号" disabled>
				</div>
			</div>
            <div class="layui-form-item">
                <label class="layui-form-label">发票号</label>
                <div class="layui-input-inline w300">
                    <input type="text" class="layui-input" name="invoice_no" value="{$info.invoice_no}" placeholder="请填写发票号" disabled>
                </div>
            </div>
            <div class="layui-form-item">
                <label class="layui-form-label">发票日期</label>
                <div class="layui-input-inline w300">
                    <input type="text" class="layui-input" name="invoice_date" value="{$info.invoice_date}" placeholder="请填写发票日期" id="invoice_date">
                </div>
            </div>
            <div class="layui-form-item">
                <label class="layui-form-label">店铺名</label>
                <div class="layui-input-inline w300">
                    <input type="text" class="layui-input" name="user_account" value="{$info.user_account}" placeholder="请填写店铺名">
                </div>
            </div>
            <div class="layui-form-item">
                <label class="layui-form-label">发票总销售</label>
                <div class="layui-input-inline w300">
                    <input type="text" class="layui-input" name="sale_amount" value="{$info.sale_amount}" placeholder="请填写发票总销售">
                </div>
            </div>
            <div class="layui-form-item">
                <label class="layui-form-label">佣金比例</label>
                <div class="layui-input-inline w300">
                    <input type="text" class="layui-input" name="commission_rate" value="{$info.commission_rate}" placeholder="请填写佣金比例">
                </div>
            </div>
            <div class="layui-form-item">
                <label class="layui-form-label">发票佣金</label>
                <div class="layui-input-inline w300">
                    <input type="text" class="layui-input" name="commission" value="{$info.commission}" placeholder="请填写发票佣金">
                </div>
            </div>
            <div class="layui-form-item">
                <label class="layui-form-label">发票应收</label>
                <div class="layui-input-inline w300">
                    <input type="text" class="layui-input" name="collection" value="{$info.collection}" placeholder="请填写发票应收">
                </div>
            </div>
            <div class="layui-form-item">
                <label class="layui-form-label">核算月份</label>
                <div class="layui-input-inline w300">
                    <input type="text" class="layui-input" name="calculate_month" value="{$info.calculate_month}" placeholder="请填写核算月份">
                </div>
            </div>
            <div class="layui-form-item">
                <label class="layui-form-label">回款金额</label>
                <div class="layui-input-inline w300">
                    <input type="text" class="layui-input" name="payment_collection" value="{$info.payment_collection}" placeholder="请填写回款金额">
                </div>
            </div>
            <div class="layui-form-item">
                <label class="layui-form-label">回款日期</label>
                <div class="layui-input-inline w300">
                    <input type="text" class="layui-input" name="payment_date" value="{$info.payment_date}" placeholder="请填写回款日期" id="payment_date">
                </div>
            </div>
            <div class="layui-form-item">
                <label class="layui-form-label">回款批次</label>
                <div class="layui-input-inline w300">
                    <input type="text" class="layui-input" name="payment_batch" value="{$info.payment_batch}" placeholder="请填写回款批次">
                </div>
            </div>
            <div class="layui-form-item">
                <label class="layui-form-label">回款周期</label>
                <div class="layui-input-inline w300">
                    <input type="text" class="layui-input" name="payment_cycle" value="{$info.payment_cycle}" placeholder="请填写回款周期">
                </div>
            </div>
            <div class="layui-form-item">
                <label class="layui-form-label">类型</label>
                <div class="layui-input-block w300"">
                    <select name="status" lay-verify="required">
                        <option value="1" {if condition="$info.status eq 1"}selected{/if}>已发货</option>
                        <option value="2" {if condition="$info.status eq 2"}selected{/if}>退款</option>
                        <option value="3" {if condition="$info.status eq 3"}selected{/if}>调整</option>
                    </select>
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
        elem: '#invoice_date',
        type: 'datetime'
    });

    laydate.render({
        elem: '#payment_date',
        type: 'datetime'
    });

	//监听提交
	form.on('submit(formCoding)', function(data){
		var text = $(this).text(),
			button = $(this);
		$('button').attr('disabled',true);
		button.text('请稍候...');
        axios.post("{:url('wayfair_core_edit', ['id' => $info['id']])}", data.field)
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
