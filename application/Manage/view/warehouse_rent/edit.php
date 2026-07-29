
{include file="public/header" /}

<!-- 主体内容 -->
<div class="layui-body" id="LAY_app_body">
    <div class="right">
        <a href="{:session('manage.back_url')}" class="layui-btn layui-btn-danger layui-btn-sm fr"><i class="layui-icon">&#xe603;</i>返回上一页</a>
        <div class="title">编辑</div>
        <div class="layui-form">
            <div class="layui-form-item">
                <label class="layui-form-label">标题</label>
                <div class="layui-input-inline w300">
                    <input type="text" class="layui-input" name="title" value="{$info.title}">
                </div>
            </div>
            <div class="layui-form-item">
                <label class="layui-form-label">开始时间</label>
                <div class="layui-input-inline w300">
                    <input type="text" class="layui-input" id="start_date" name="start_date" value="{if condition='$info.start_date neq null'}{:date('Y-m-d', strtotime($info['start_date']))}{/if}">
                </div>
            </div>
            <div class="layui-form-item">
                <label class="layui-form-label">结束时间</label>
                <div class="layui-input-inline w300">
                    <input type="text" class="layui-input" id="end_date" name="end_date" value="{if condition='$info.end_date neq null'}{:date('Y-m-d', strtotime($info['end_date']))}{/if}">
                </div>
            </div>
            <div class="layui-form-item">
                <div class="layui-input-block">
                    <button class="layui-btn w200" lay-submit lay-filter="formCoding">提交保存</button>
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
            elem: '#start_date',
            type: 'date'
        });

        // 显示日期选择器
        laydate.render({
            elem: '#end_date',
            type: 'date'
        });

        //监听提交
        form.on('submit(formCoding)', function(data){
            var text = $(this).text(),
                button = $(this);
            $('button').attr('disabled',true);
            button.text('请稍候...');
            axios.post("{:url('edit', ['id' => $info['id']])}", data.field)
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
