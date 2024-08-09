
{include file="public/header" /}

<!-- 主体内容 -->
<div class="layui-body" id="LAY_app_body">
    <div class="right">
        <a href="{:session('back_url', '', 'manage')}" class="layui-btn layui-btn-danger layui-btn-sm fr"><i class="layui-icon">&#xe603;</i>返回上一页</a>
        <div class="title">添加映射关系</div>
        <div class="layui-form">
            <div class="layui-form-item">
                <label class="layui-form-label">店铺SKU</label>
                <div class="layui-input-inline w300">
                    <input type="text" class="layui-input" name="seller_sku" value="{$info.seller_sku}" placeholder="请填写店铺SKU">
                </div>
            </div>
            <div class="layui-form-item">
                <label class="layui-form-label">仓库SKU</label>
                <div class="layui-input-inline w300">
                    <input type="text" class="layui-input" name="warehouse_sku" value="{$info.warehouse_sku}" placeholder="请选择仓库SKU">
                </div>
            </div>
            <div class="layui-form-item">
                <label class="layui-form-label">数量</label>
                <div class="layui-input-inline w300">
                    <input type="text" class="layui-input" name="qty" value="{$info.qty}" placeholder="请选择数量">
                </div>
            </div>
            <div class="layui-form-item">
                <label class="layui-form-label">占比</label>
                <div class="layui-input-inline w300">
                    <input type="text" class="layui-input" name="percent" value="{$info.percent}" value="1" placeholder="请选择占比">
                </div>
            </div>
            <div class="layui-form-item">
                <label class="layui-form-label">店铺名</label>
                <div class="layui-input-inline w300">
                    <input type="text" class="layui-input" name="user_account" value="{$info.user_account}" placeholder="请选择店铺名">
                </div>
            </div>
            <div class="layui-form-item">
                <label class="layui-form-label">运营人员</label>
                <div class="layui-input-inline w300">
                    <input type="text" class="layui-input" name="seller" value="{$info.seller}" placeholder="请选择运营人员">
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
            axios.post("{:url('relation_edit', ['id' => $info['id']])}", data.field)
                .then(function (response) {
                    let res = response.data;
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
