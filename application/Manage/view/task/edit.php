
{include file="public/header" /}

<!-- 主体内容 -->
<div class="layui-body" id="LAY_app_body">
    <div class="right">
        <a href="{:url('index')}" class="layui-btn layui-btn-danger layui-btn-sm fr"><i class="layui-icon">&#xe603;</i>返回上一页</a>
        <div class="title">编辑任务</div>
        <div class="layui-form">
            <div class="layui-form-item">
                <label class="layui-form-label">事项内容</label>
                <div class="layui-input-inline w300">
                    <input type="text" class="layui-input" name="title" value="{$info.title}" placeholder="请填写事项内容">
                </div>
            </div>
            <div class="layui-form-item">
                <label class="layui-form-label">主负责人</label>
                <div class="layui-input-inline w300">
                    <div id="user_master"></div>
                </div>
            </div>
            <div class="layui-form-item">
                <label class="layui-form-label">相关人员</label>
                <div class="layui-input-inline w300">
                    <div id="user_support"></div>
                </div>
            </div>
            <div class="layui-form-item">
                <label class="layui-form-label">ECT(预计)</label>
                <div class="layui-input-inline w300">
                    <input type="text" class="layui-input" id="ect_time" name="ect_time" value="{$info.ect_time}" placeholder="ECT预计">
                </div>
            </div>
            <div class="layui-form-item">
                <label class="layui-form-label">ACT(实际)</label>
                <div class="layui-input-inline w300">
                    <input type="text" class="layui-input" id="act_time" name="act_time" value="{$info.act_time}" placeholder="ACT实际">
                </div>
            </div>
            <div class="layui-form-item">
                <label class="layui-form-label">备注</label>
                <div class="layui-input-inline w300">
                    <textarea name="remarks" class="layui-textarea">{$info.remarks}</textarea>
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
    layui.use(['form', 'jquery', 'laydate'], function(){
        let $ = layui.jquery,
            form = layui.form,
            xmSelect = layui.xmSelect,
            laydate = layui.laydate;

        // 显示日期选择器
        laydate.render({
            elem: '#ect_time',
            type: 'datetime'
        });

        // 显示日期选择器
        laydate.render({
            elem: '#act_time',
            type: 'datetime'
        });

        // 启用搜索
        // var xmSelect = layui.xmSelect; // 确保 layui.use 已加载 xmSelect
        let select = xmSelect.render({
            el: '#user_master',
            name: 'user_master',
            radio: true,
            clickClose: true,
            filterable: true,
            remoteSearch: true,   // 开启远程搜索
            initValue: ['1'],
            remoteMethod: function(val, cb) {
                // val 是输入值
                // cb 是回调函数，用于返回数据
                $.get("{:url('Check/get_task_active_user')}", {search: val, selected: "{$info.user_master}"}, function(res){
                    // cb(res.data); // 返回数组 [{name:'苹果',value:'1'}, ...]
                    let result = JSON.parse(res).filter(function(item){
                        return item.name.indexOf(val) !== -1;
                    });
                    cb(result);
                });
            }
        });

        let select2 = xmSelect.render({
            el: '#user_support',
            name: 'user_support',
            filterable: true,
            remoteSearch: true,   // 开启远程搜索
            remoteMethod: function(val, cb) {
                // val 是输入值
                // cb 是回调函数，用于返回数据
                $.get("{:url('Check/get_task_active_user')}", {search: val, selected: "{$info.user_support}"}, function(res){
                    // cb(res.data); // 返回数组 [{name:'苹果',value:'1'}, ...]
                    let result = JSON.parse(res).filter(function(item){
                        return item.name.indexOf(val) !== -1;
                    });
                    cb(result);
                });
            }
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
                            location.href = "{:session('back_url', '', 'manage')}"
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
