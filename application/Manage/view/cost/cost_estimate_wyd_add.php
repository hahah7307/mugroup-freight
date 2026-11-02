
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
                    <input type="text" class="layui-input" name="warehouse">
                </div>
            </div>
            <div class="layui-form-item">
                <label class="layui-form-label">物流产品</label>
                <div class="layui-input-inline w300">
                    <input type="text" class="layui-input" name="logisticsProductsCode">
                </div>
            </div>
            <div class="layui-form-item">
                <label class="layui-form-label">物流服务商</label>
                <div class="layui-input-inline w300">
                    <input type="text" class="layui-input" name="logisticsProviderCode">
                </div>
            </div>
            <div class="layui-form-item">
                <label class="layui-form-label">国家和地区<span class="red">*</span></label>
                <div class="layui-input-inline w300">
                    <input type="text" class="layui-input" name="countryCode">
                </div>
            </div>
            <div class="layui-form-item">
                <label class="layui-form-label">省/州</label>
                <div class="layui-input-inline w300">
                    <input type="text" class="layui-input" name="province">
                </div>
            </div>
            <div class="layui-form-item">
                <label class="layui-form-label">城市</label>
                <div class="layui-input-inline w300">
                    <input type="text" class="layui-input" name="city">
                </div>
            </div>
            <div class="layui-form-item">
                <label class="layui-form-label">地址1</label>
                <div class="layui-input-inline w300">
                    <input type="text" class="layui-input" name="address1">
                </div>
            </div>
            <div class="layui-form-item">
                <label class="layui-form-label">地址2</label>
                <div class="layui-input-inline w300">
                    <input type="text" class="layui-input" name="address2">
                </div>
            </div>
            <div class="layui-form-item">
                <label class="layui-form-label">邮编<span class="red">*</span></label>
                <div class="layui-input-inline w300">
                    <input type="text" class="layui-input" name="postCode">
                </div>
            </div>
            <div class="layui-form-item">
                <label class="layui-form-label">包裹长<span class="red">*</span></label>
                <div class="layui-input-inline w300">
                    <input type="text" class="layui-input" name="length">
                </div>
            </div>
            <div class="layui-form-item">
                <label class="layui-form-label">包裹宽<span class="red">*</span></label>
                <div class="layui-input-inline w300">
                    <input type="text" class="layui-input" name="width">
                </div>
            </div>
            <div class="layui-form-item">
                <label class="layui-form-label">包裹高<span class="red">*</span></label>
                <div class="layui-input-inline w300">
                    <input type="text" class="layui-input" name="height">
                </div>
            </div>
            <div class="layui-form-item">
                <label class="layui-form-label">包裹重量<span class="red">*</span></label>
                <div class="layui-input-inline w300">
                    <input type="text" class="layui-input" name="weight">
                </div>
            </div>
            <div class="layui-form-item">
                <label class="layui-form-label">签名服务<span class="red">*</span></label>
                <div class="layui-input-inline w300">
                    <select name="needSignature" lay-verify="">
                        <option value="0" selected>不需要</option>
                        <option value="1">需要</option>
                    </select>
                </div>
            </div>
            <div class="layui-form-item">
                <label class="layui-form-label">发货类型<span class="red">*</span></label>
                <div class="layui-input-inline w300">
                    <select name="deliveryStyle" lay-verify="">
                        <option value="1" selected>⽆忧达快递</option>
                        <option value="2">⾃上传</option>
                    </select>
                </div>
            </div>
            <div class="layui-form-item">
                <label class="layui-form-label">保险⾦额</label>
                <div class="layui-input-inline w300">
                    <input type="text" class="layui-input" name="insureAmount">
                </div>
            </div>
            <div class="layui-form-item">
                <label class="layui-form-label">保险币种</label>
                <div class="layui-input-inline w300">
                    <input type="text" class="layui-input" name="insureCurrency">
                </div>
            </div>
            <div class="layui-form-item">
                <label class="layui-form-label">订单加急渠道</label>
                <div class="layui-input-inline w300">
                    <input type="text" class="layui-input" name="urgentChannel">
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
            axios.post("{:url('cost_estimate_wyd_add')}", data.field)
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
