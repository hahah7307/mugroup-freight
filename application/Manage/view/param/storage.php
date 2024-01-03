
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
                    <label class="layui-form-label">抓取订单每页条数</label>
                    <div class="layui-input-inline">
                        <input type="text" class="layui-input w300" name="order_page_num" value="{$config['order_page_num']}">
                    </div>
                </div>
            </div>
        </div>
        <div>
            <div class="layui-form-item">
                <div class="layui-inline layui-col-md3">
                    <label class="layui-form-label">燃油费(%)</label>
                    <div class="layui-input-inline">
                        <input type="text" class="layui-input w300" name="fuel_cost" value="{$config['fuel_cost']}">
                    </div>
                </div>
            </div>
        </div>
		<div>
            <div class="layui-form-item">
                <div class="layui-inline layui-col-md3">
                    <label class="layui-form-label">AHS旺季时间（乐仓）</label>
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
                    <label class="layui-form-label">附加费金额（乐仓）</label>
                    <div class="layui-input-inline">
                        <input type="text" class="layui-input w300" name="liang_additional_fee" value="{$config['liang_additional_fee']}">
                    </div>
                </div>
            </div>
        </div>
        <div>
            <div class="layui-form-item">
                <div class="layui-inline layui-col-md3">
                    <label class="layui-form-label">AHS旺季时间（乐歌）</label>
                    <div class="layui-input-inline">
                        <input type="text" class="layui-input w300" id="ahs_additional_time3" name="ahs_additional_time3" value="{$config['ahs_additional_time3']}" placeholder="开始时间">
                    </div>
                </div>
            </div>
        </div>
        <div>
            <div class="layui-form-item">
                <div class="layui-inline layui-col-md3">
                    <label class="layui-form-label"></label>
                    <div class="layui-input-inline">
                        <input type="text" class="layui-input w300" id="ahs_additional_time4" name="ahs_additional_time4" value="{$config['ahs_additional_time4']}" placeholder="结束时间">
                    </div>
                </div>
            </div>
        </div>
        <div>
            <div class="layui-form-item">
                <div class="layui-inline layui-col-md3">
                    <label class="layui-form-label">附加费金额（乐歌）</label>
                    <div class="layui-input-inline">
                        <input type="text" class="layui-input w300" name="loctek_additional_fee" value="{$config['loctek_additional_fee']}">
                    </div>
                </div>
            </div>
        </div>
        <div>
            <div class="layui-form-item">
                <div class="layui-inline layui-col-md3">
                    <label class="layui-form-label">仅出库费平台（Json）</label>
                    <div class="layui-input-inline">
                        <textarea name="outbound_platform" class="layui-textarea w300">{$config['outbound_platform']}</textarea>
                    </div>
                </div>
            </div>
        </div>
        <div class="title">导入中心</div>
        <div>
            <div class="layui-form-item">
                <div class="layui-inline layui-col-md3">
                    <label class="layui-form-label">良仓总列数</label>
                    <div class="layui-input-inline">
                        <input type="text" class="layui-input w300" name="excel_col_liang" value="{$config['excel_col_liang']}">
                    </div>
                </div>
                <div class="layui-inline layui-col-md3">
                    <label class="layui-form-label">乐仓总列数</label>
                    <div class="layui-input-inline">
                        <input type="text" class="layui-input w300" name="excel_col_loctek" value="{$config['excel_col_loctek']}">
                    </div>
                </div>
            </div>
        </div>
        <div>
            <div class="layui-form-item">
                <div class="layui-inline layui-col-md3">
                    <label class="layui-form-label">订单号列</label>
                    <div class="layui-input-inline">
                        <input type="text" class="layui-input w300" name="excel_ordercode_liang" value="{$config['excel_ordercode_liang']}">
                    </div>
                </div>
                <div class="layui-inline layui-col-md3">
                    <label class="layui-form-label"></label>
                    <div class="layui-input-inline">
                        <input type="text" class="layui-input w300" name="excel_ordercode_loctek" value="{$config['excel_ordercode_loctek']}">
                    </div>
                </div>
            </div>
        </div>
        <div>
            <div class="layui-form-item">
                <div class="layui-inline layui-col-md3">
                    <label class="layui-form-label">邮编列</label>
                    <div class="layui-input-inline">
                        <input type="text" class="layui-input w300" name="excel_postal_liang" value="{$config['excel_postal_liang']}">
                    </div>
                </div>
                <div class="layui-inline layui-col-md3">
                    <label class="layui-form-label"></label>
                    <div class="layui-input-inline">
                        <input type="text" class="layui-input w300" name="excel_postal_loctek" value="{$config['excel_postal_loctek']}">
                    </div>
                </div>
            </div>
        </div>
        <div>
            <div class="layui-form-item">
                <div class="layui-inline layui-col-md3">
                    <label class="layui-form-label">计费重列</label>
                    <div class="layui-input-inline">
                        <input type="text" class="layui-input w300" name="excel_weight_liang" value="{$config['excel_weight_liang']}">
                    </div>
                </div>
                <div class="layui-inline layui-col-md3">
                    <label class="layui-form-label"></label>
                    <div class="layui-input-inline">
                        <input type="text" class="layui-input w300" name="excel_weight_loctek" value="{$config['excel_weight_loctek']}">
                    </div>
                </div>
            </div>
        </div>
        <div>
            <div class="layui-form-item">
                <div class="layui-inline layui-col-md3">
                    <label class="layui-form-label">住宅地址附加费列</label>
                    <div class="layui-input-inline">
                        <input type="text" class="layui-input w300" name="excel_rdc_liang" value="{$config['excel_rdc_liang']}">
                    </div>
                </div>
                <div class="layui-inline layui-col-md3">
                    <label class="layui-form-label"></label>
                    <div class="layui-input-inline">
                        <input type="text" class="layui-input w300" name="excel_rdc_loctek" value="{$config['excel_rdc_loctek']}">
                    </div>
                </div>
            </div>
        </div>
        <div>
            <div class="layui-form-item">
                <div class="layui-inline layui-col-md3">
                    <label class="layui-form-label">住宅地址旺季附加费列</label>
                    <div class="layui-input-inline">
                        <input type="text" class="layui-input w300" name="excel_drdc_liang" value="{$config['excel_drdc_liang']}">
                    </div>
                </div>
                <div class="layui-inline layui-col-md3">
                    <label class="layui-form-label"></label>
                    <div class="layui-input-inline">
                        <input type="text" class="layui-input w300" name="excel_drdc_loctek" value="{$config['excel_drdc_loctek']}">
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
        elem: '#ahs_additional_time3',
        type: 'datetime'
    });
    laydate.render({
        elem: '#ahs_additional_time4',
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
