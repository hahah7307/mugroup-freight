
{include file="public/header" /}

<!-- 主体内容 -->
<div class="layui-body" id="LAY_app_body">
    <div class="right">
        <a href="{:url('index')}" class="layui-btn layui-btn-danger layui-btn-sm fr"><i class="layui-icon">&#xe603;</i>返回上一页</a>
        <div class="title">添加AHS费用</div>
        <div class="layui-form">
            <div class="layui-form-item">
                <label class="layui-form-label">所属仓库</label>
                <div class="layui-input-inline w300">
                    <select name="storage_id" lay-verify="">
                        {foreach name="storage" item="v"}
                        <option value="{$v.id}">{$v.name}</option>
                        {/foreach}
                    </select>
                </div>
            </div>
            <div class="layui-form-item">
                <label class="layui-form-label">所属规则</label>
                <div class="layui-input-inline w300">
                    <select name="ahs_id" lay-verify="">
                        {foreach name="ahs" item="va"}
                        <option value="{$va.id}">{$va.type_code}</option>
                        {/foreach}
                    </select>
                </div>
            </div>
            <div class="layui-form-item">
                <label class="layui-form-label">Zone</label>
                <div class="layui-input-inline w300">
                    <input type="text" class="layui-input" name="zone">
                </div>
            </div>
            <div class="layui-form-item">
                <label class="layui-form-label">金额</label>
                <div class="layui-input-inline w300">
                    <input type="text" class="layui-input" name="value">
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
            axios.post("{:url('add')}", data.field)
                .then(function (response) {
                    let res = response.data;
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