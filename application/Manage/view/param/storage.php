
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
                    <label class="layui-form-label">AHS旺季时间（良仓）</label>
                    <div class="layui-input-inline">
                        <input type="text" class="layui-input w300" id="ahs_additional_time" name="ahs_additional_time" value="{$config['ahs_additional_time']}" placeholder="开始时间">
                    </div>
                </div>
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
                        <input type="text" class="layui-input w300" id="ahs_additional_time2" name="ahs_additional_time2" value="{$config['ahs_additional_time2']}" placeholder="结束时间">
                    </div>
                </div>
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
                    <label class="layui-form-label">RDC旺季时间（良仓）</label>
                    <div class="layui-input-inline">
                        <input type="text" class="layui-input w300" id="rdc_additional_time5" name="rdc_additional_time5" value="{$config['rdc_additional_time5']}" placeholder="开始时间">
                    </div>
                </div>
                <div class="layui-inline layui-col-md3">
                    <label class="layui-form-label">RDC旺季时间（乐歌）</label>
                    <div class="layui-input-inline">
                        <input type="text" class="layui-input w300" id="rdc_additional_time7" name="rdc_additional_time7" value="{$config['rdc_additional_time7']}" placeholder="开始时间">
                    </div>
                </div>
            </div>
        </div>
        <div>
            <div class="layui-form-item">
                <div class="layui-inline layui-col-md3">
                    <label class="layui-form-label"></label>
                    <div class="layui-input-inline">
                        <input type="text" class="layui-input w300" id="rdc_additional_time6" name="rdc_additional_time6" value="{$config['rdc_additional_time6']}" placeholder="结束时间">
                    </div>
                </div>
                <div class="layui-inline layui-col-md3">
                    <label class="layui-form-label"></label>
                    <div class="layui-input-inline">
                        <input type="text" class="layui-input w300" id="rdc_additional_time8" name="rdc_additional_time8" value="{$config['rdc_additional_time8']}" placeholder="结束时间">
                    </div>
                </div>
            </div>
        </div>
        <div>
            <div class="layui-form-item">
                <div class="layui-inline layui-col-md3">
                    <label class="layui-form-label">AHS旺季附加费(良仓)</label>
                    <div class="layui-input-inline">
                        <input type="text" class="layui-input w300" name="liang_additional_fee" value="{$config['liang_additional_fee']}">
                    </div>
                </div>
                <div class="layui-inline layui-col-md3">
                    <label class="layui-form-label">AHS旺季附加费(乐歌)</label>
                    <div class="layui-input-inline">
                        <input type="text" class="layui-input w300" name="loctek_additional_fee" value="{$config['loctek_additional_fee']}">
                    </div>
                </div>
            </div>
        </div>
        <div>
            <div class="layui-form-item">
                <div class="layui-inline layui-col-md3">
                    <label class="layui-form-label">住宅地址附加费(良仓)</label>
                    <div class="layui-input-inline">
                        <input type="text" class="layui-input w300" name="liang_rdc_fee" value="{$config['liang_rdc_fee']}">
                    </div>
                </div>
                <div class="layui-inline layui-col-md3">
                    <label class="layui-form-label">住宅地址附加费(乐歌)</label>
                    <div class="layui-input-inline">
                        <input type="text" class="layui-input w300" name="loctek_rdc_fee" value="{$config['loctek_rdc_fee']}">
                    </div>
                </div>
            </div>
        </div>
        <div>
            <div class="layui-form-item">
                <div class="layui-inline layui-col-md3">
                    <label class="layui-form-label">住宅旺季附加费(良仓)</label>
                    <div class="layui-input-inline">
                        <input type="text" class="layui-input w300" name="liang_drdc_fee" value="{$config['liang_drdc_fee']}">
                    </div>
                </div>
                <div class="layui-inline layui-col-md3">
                    <label class="layui-form-label">住宅旺季附加费(乐歌)</label>
                    <div class="layui-input-inline">
                        <input type="text" class="layui-input w300" name="loctek_drdc_fee" value="{$config['loctek_drdc_fee']}">
                    </div>
                </div>
            </div>
        </div>
        <div>
            <div class="layui-form-item">
                <div class="layui-inline layui-col-md3">
                    <label class="layui-form-label">燃油费率(%)</label>
                    <div class="layui-input-inline">
                        <input type="text" class="layui-input w300" name="fuel_cost" value="{$config['fuel_cost']}">
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
        <div class="title">自动任务中心</div>
        <div>
            <div class="layui-form-item">
                <div class="layui-inline layui-col-md3">
                    <label class="layui-form-label">订单抓取数/分钟</label>
                    <div class="layui-input-inline">
                        <input type="text" class="layui-input w300" name="order_page_num" value="{$config['order_page_num']}">
                    </div>
                </div>
            </div>
        </div>
        <div>
            <div class="layui-form-item">
                <div class="layui-inline layui-col-md3">
                    <label class="layui-form-label">订单关联尾程数/分钟</label>
                    <div class="layui-input-inline">
                        <input type="text" class="layui-input w300" name="finance_notify_num" value="{$config['finance_notify_num']}">
                    </div>
                </div>
            </div>
        </div>
        <div>
            <div class="layui-form-item">
                <div class="layui-inline layui-col-md3">
                    <label class="layui-form-label">邮编更新数/分钟</label>
                    <div class="layui-input-inline">
                        <input type="text" class="layui-input w300" name="postal_update_num" value="{$config['postal_update_num']}">
                    </div>
                </div>
            </div>
        </div>
        <div>
            <div class="layui-form-item">
                <div class="layui-inline layui-col-md3">
                    <label class="layui-form-label">仓储费运算数/分钟</label>
                    <div class="layui-input-inline">
                        <input type="text" class="layui-input w300" name="inventory_batch_num" value="{$config['inventory_batch_num']}">
                    </div>
                </div>
            </div>
        </div>
        <div class="title">账号中心</div>
        <div>
            <div class="layui-form-item">
                <div class="layui-inline layui-col-md3">
                    <label class="layui-form-label">易仓WMS地址</label>
                    <div class="layui-input-inline">
                        <input type="text" class="layui-input w300" name="ec_wms_uri" value="{$config['ec_wms_uri']}">
                    </div>
                </div>
            </div>
        </div>
        <div>
            <div class="layui-form-item">
                <div class="layui-inline layui-col-md3">
                    <label class="layui-form-label">易仓EB地址</label>
                    <div class="layui-input-inline">
                        <input type="text" class="layui-input w300" name="ec_eb_uri" value="{$config['ec_eb_uri']}">
                    </div>
                </div>
            </div>
        </div>
        <div>
            <div class="layui-form-item">
                <div class="layui-inline layui-col-md3">
                    <label class="layui-form-label">易仓账户</label>
                    <div class="layui-input-inline">
                        <input type="text" class="layui-input w300" name="ec_warehouse_username" value="{$config['ec_warehouse_username']}">
                    </div>
                </div>
            </div>
        </div>
        <div>
            <div class="layui-form-item">
                <div class="layui-inline layui-col-md3">
                    <label class="layui-form-label">易仓密码</label>
                    <div class="layui-input-inline">
                        <input type="text" class="layui-input w300" name="ec_warehouse_userpass" value="{$config['ec_warehouse_userpass']}">
                    </div>
                </div>
            </div>
        </div>
        <div>
            <div class="layui-form-item">
                <div class="layui-inline layui-col-md3">
                    <label class="layui-form-label">良仓账户</label>
                    <div class="layui-input-inline">
                        <input type="text" class="layui-input w300" name="lc_warehouse_username" value="{$config['lc_warehouse_username']}">
                    </div>
                </div>
            </div>
        </div>
        <div>
            <div class="layui-form-item">
                <div class="layui-inline layui-col-md3">
                    <label class="layui-form-label">良仓密码</label>
                    <div class="layui-input-inline">
                        <input type="text" class="layui-input w300" name="lc_warehouse_userpass" value="{$config['lc_warehouse_userpass']}">
                    </div>
                </div>
            </div>
        </div>
        <div>
            <div class="layui-form-item">
                <div class="layui-inline layui-col-md3">
                    <label class="layui-form-label">乐仓账户</label>
                    <div class="layui-input-inline">
                        <input type="text" class="layui-input w300" name="le_warehouse_username" value="{$config['le_warehouse_username']}">
                    </div>
                </div>
            </div>
        </div>
        <div>
            <div class="layui-form-item">
                <div class="layui-inline layui-col-md3">
                    <label class="layui-form-label">乐仓密码</label>
                    <div class="layui-input-inline">
                        <input type="text" class="layui-input w300" name="le_warehouse_userpass" value="{$config['le_warehouse_userpass']}">
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
    laydate.render({
        elem: '#rdc_additional_time5',
        type: 'datetime'
    });
    laydate.render({
        elem: '#rdc_additional_time6',
        type: 'datetime'
    });

    laydate.render({
        elem: '#rdc_additional_time7',
        type: 'datetime'
    });
    laydate.render({
        elem: '#rdc_additional_time8',
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
