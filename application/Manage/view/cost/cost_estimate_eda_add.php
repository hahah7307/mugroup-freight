
{include file="public/header" /}

<!-- 主体内容 -->
<div class="layui-body" id="LAY_app_body">
    <div class="right">
        <a href="{:session('manage.back_url')}" class="layui-btn layui-btn-danger layui-btn-sm fr"><i class="layui-icon">&#xe603;</i>返回上一页</a>
        <div class="title">新增尾程试算</div>
        <div class="layui-form">
            <div class="layui-form-item">
                <label class="layui-form-label">发货仓代码<span class="red">*</span></label>
                <div class="layui-input-inline w300">
                    <input type="text" class="layui-input" name="warehouseCode">
                </div>
            </div>
            <div class="layui-form-item">
                <label class="layui-form-label">物流产品id</label>
                <div class="layui-input-inline w300">
                    <input type="text" class="layui-input" name="logisticsProductIds">
                </div>
            </div>
            <div class="layui-form-item">
                <label class="layui-form-label">目的国编码<span class="red">*</span></label>
                <div class="layui-input-inline w300">
                    <input type="text" class="layui-input" name="endCountryCode">
                </div>
            </div>
            <div class="layui-form-item">
                <label class="layui-form-label">城市</label>
                <div class="layui-input-inline w300">
                    <input type="text" class="layui-input" name="city">
                </div>
            </div>
            <div class="layui-form-item">
                <label class="layui-form-label">邮编<span class="red">*</span></label>
                <div class="layui-input-inline w300">
                    <input type="text" class="layui-input" name="endZipcode">
                </div>
            </div>
            <div class="layui-form-item">
                <label class="layui-form-label">包裹类型<span class="red">*</span></label>
                <div class="layui-input-inline w300">
                    <select name="manualInputSwitch" lay-verify="">
                        <option value="0" selected>通过SKU预估</option>
                        <option value="1">通过包裹信息预估</option>
                    </select>
                </div>
            </div>
            <div class="layui-form-item">
                <label class="layui-form-label">货物类型<span class="red">*</span></label>
                <div class="layui-input-inline w300">
                    <select name="estimatedGoodsType" lay-verify="">
                        <option value="GENERAL_CARGO" selected>普通货物（不带电）</option>
                        <option value="PURE_BATTERY">纯电池</option>
                        <option value="SUPPORTING_BATTERY">配套电池</option>
                        <option value="BUILT_IN_BATTERY">内置电池</option>
                    </select>
                </div>
            </div>
            <div class="layui-form-item">
                <label class="layui-form-label">SKU<span class="red">*</span></label>
                <div class="layui-input-inline w300">
                    <input type="text" class="layui-input" name="skuInfoDetails[skuCode]">
                </div>
            </div>
            <div class="layui-form-item">
                <label class="layui-form-label">包裹数<span class="red">*</span></label>
                <div class="layui-input-inline w300">
                    <input type="text" class="layui-input" name="skuInfoDetails[quantity]" value="1">
                </div>
            </div>
            <div class="layui-form-item">
                <label class="layui-form-label">包裹长（cm）<span class="red">*</span></label>
                <div class="layui-input-inline w300">
                    <input type="text" class="layui-input" name="packageDetails[length]">
                </div>
            </div>
            <div class="layui-form-item">
                <label class="layui-form-label">包裹宽（cm）<span class="red">*</span></label>
                <div class="layui-input-inline w300">
                    <input type="text" class="layui-input" name="packageDetails[width]">
                </div>
            </div>
            <div class="layui-form-item">
                <label class="layui-form-label">包裹高（cm）<span class="red">*</span></label>
                <div class="layui-input-inline w300">
                    <input type="text" class="layui-input" name="packageDetails[height]">
                </div>
            </div>
            <div class="layui-form-item">
                <label class="layui-form-label">包裹重（g）<span class="red">*</span></label>
                <div class="layui-input-inline w300">
                    <input type="text" class="layui-input" name="packageDetails[weight]">
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
            axios.post("{:url('cost_estimate_eda_add')}", data.field, {
                headers: {
                    'Content-Type': 'multipart/form-data' // 设置请求头，确保服务器正确解析 FormData
                }
            })
                .then(function (response) {
                    let res = response.data;
                    if (res.code === 1) {
                        layer.alert(res.msg,{icon:1,closeBtn:0,title:false,btnAlign:'c',},function(){
                            location.href = "{:session('manage.back_url')}";
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
