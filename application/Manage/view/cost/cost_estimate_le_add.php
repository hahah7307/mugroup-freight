
{include file="public/header" /}

<!-- 主体内容 -->
<div class="layui-body" id="LAY_app_body">
    <div class="right">
        <a href="{:session('manage.back_url')}" class="layui-btn layui-btn-danger layui-btn-sm fr"><i class="layui-icon">&#xe603;</i>返回上一页</a>
        <div class="title">新增尾程试算</div>
        <div class="layui-form">
            <div class="layui-form-item">
                <label class="layui-form-label">仓库代码<span class="red">*</span></label>
                <div class="layui-input-inline w300">
                    <input type="text" class="layui-input" name="warehouseCode">
                </div>
            </div>
            <div class="layui-form-item">
                <label class="layui-form-label">运输渠道<span class="red">*</span></label>
                <div class="layui-input-inline w300">
                    <select name="productName" lay-verify="">
                        <option value="FEDEX_GROUND">FEDEX_GROUND</option>
                        <option value="FEDEX_HOME_DELIVERY">FEDEX_HOME_DELIVERY</option>
                        <option value="UPS_GROUND">UPS_GROUND</option>
                        <option value="Amazon_Ground">Amazon_Ground</option>
                        <option value="FEDEX_GROUND_SFP">FEDEX_GROUND_SFP</option>
                        <option value="FEDEX_HOME_DELIVEY_SFP">FEDEX_HOME_DELIVEY_SFP</option>
                        <option value="usps_ground_advantage1">usps_ground_advantage1</option>
                        <option value="FEDEX_ECONOMY">FEDEX_ECONOMY</option>
                        <option value="CEVA_LTL_HOME">CEVA_LTL_HOME</option>
                        <option value="CEVA_LTL_GROUND">CEVA_LTL_GROUND</option>
                        <option value="WPGROUND">WPGROUND</option>
                        <option value="UPS_ROADIE_GROUND">UPS_ROADIE_GROUND</option>
                    </select>
                </div>
            </div>
            <div class="layui-form-item">
                <label class="layui-form-label">国家二字码<span class="red">*</span></label>
                <div class="layui-input-inline w300">
                    <input type="text" class="layui-input" name="countryCode" value="US">
                </div>
            </div>
            <div class="layui-form-item">
                <label class="layui-form-label">州省二字码<span class="red">*</span></label>
                <div class="layui-input-inline w300">
                    <input type="text" class="layui-input" name="province">
                </div>
            </div>
            <div class="layui-form-item">
                <label class="layui-form-label">城市<span class="red">*</span></label>
                <div class="layui-input-inline w300">
                    <input type="text" class="layui-input" name="city">
                </div>
            </div>
            <div class="layui-form-item">
                <label class="layui-form-label">地址1<span class="red">*</span></label>
                <div class="layui-input-inline w300">
                    <input type="text" class="layui-input" name="street1">
                </div>
            </div>
            <div class="layui-form-item">
                <label class="layui-form-label">地址2</label>
                <div class="layui-input-inline w300">
                    <input type="text" class="layui-input" name="street2">
                </div>
            </div>
            <div class="layui-form-item">
                <label class="layui-form-label">邮编<span class="red">*</span></label>
                <div class="layui-input-inline w300">
                    <input type="text" class="layui-input" name="postalCode">
                </div>
            </div>
            <div class="layui-form-item">
                <label class="layui-form-label">货品编码<span class="red">*</span></label>
                <div class="layui-input-inline w300">
                    <input type="text" class="layui-input" name="goodsCode">
                </div>
            </div>
            <div class="layui-form-item">
                <label class="layui-form-label">包裹长</label>
                <div class="layui-input-inline w300">
                    <input type="text" class="layui-input" name="length">
                </div>
            </div>
            <div class="layui-form-item">
                <label class="layui-form-label">包裹宽</label>
                <div class="layui-input-inline w300">
                    <input type="text" class="layui-input" name="width">
                </div>
            </div>
            <div class="layui-form-item">
                <label class="layui-form-label">包裹高</label>
                <div class="layui-input-inline w300">
                    <input type="text" class="layui-input" name="height">
                </div>
            </div>
            <div class="layui-form-item">
                <label class="layui-form-label">包裹重量</label>
                <div class="layui-input-inline w300">
                    <input type="text" class="layui-input" name="weight">
                </div>
            </div>
            <div class="layui-form-item">
                <label class="layui-form-label">签名服务</label>
                <div class="layui-input-inline w300">
                    <input type="text" class="layui-input" name="signatureService">
                </div>
            </div>
            <div class="layui-form-item">
                <label class="layui-form-label">长度单位<span class="red">*</span></label>
                <div class="layui-input-inline w300">
                    <input type="text" class="layui-input" name="lengthUnits" value="CM">
                </div>
            </div>
            <div class="layui-form-item">
                <label class="layui-form-label">重量单位<span class="red">*</span></label>
                <div class="layui-input-inline w300">
                    <input type="text" class="layui-input" name="weightUnits" value="KG">
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
            axios.post("{:url('cost_estimate_le_add')}", data.field)
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
