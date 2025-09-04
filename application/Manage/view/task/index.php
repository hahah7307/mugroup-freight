
{include file="public/header" /}

<style>
    .ellipsis {
        overflow: hidden;              /* 溢出隐藏 */
        white-space: nowrap;           /* 不换行 */
        text-overflow: ellipsis;       /* 显示省略号 */
        width: 800px;                  /* 必须有固定宽度 */
    }
</style>
<!-- 主体内容 -->
<div class="layui-body" id="LAY_app_body">
    <div class="right">
        <div class="title">任务跟踪</div>
        <form class="layui-form search-form" method="get">
            <div class="layui-inline w200">
                <input type="text" class="layui-input" name="keyword" value="{$keyword}" placeholder="">
            </div>
            <div class="layui-inline w200">
                <div id="user_master"></div>
            </div>
            <div class="layui-inline w200">
                <div id="create_id"></div>
            </div>
            <div class="layui-inline w120">
                <select name="status" lay-verify="">
                    <option value="-1">全部状态</option>
                    <option value="0" {if condition="$status eq 0"}selected{/if}>未完成</option>
                    <option value="1" {if condition="$status eq 1"}selected{/if}>已完成</option>
                </select>
            </div>
            <div class="layui-inline w120">
                <select name="time" lay-verify="">
                    <option value="1" {if condition="$time eq 1"}selected{/if}>创建时间</option>
                    <option value="2" {if condition="$time eq 2"}selected{/if}>ECT(预计)</option>
                    <option value="3" {if condition="$time eq 3"}selected{/if}>ACT(实际)</option>
                </select>
            </div>
            <div class="layui-input-inline">
                <input type="text" class="layui-input" id="export_start_time" name="start_time" value="{$start_time}" placeholder="开始时间">
            </div>
            <div class="layui-input-inline">
                <input type="text" class="layui-input" id="export_end_time" name="end_time" value="{$end_time}" placeholder="结束时间">
            </div>
            <div class="layui-inline">
                <button class="layui-btn" lay-submit lay-filter="Search"><i class="layui-icon">&#xe615;</i> 查询</button>
            </div>
            <div class="layui-inline">
                <a class="layui-btn layui-btn-normal" href="{:url('index')}"><i class="layui-icon">&#xe669;</i> 重置</a>
            </div>
        </form>

        <div class="layui-form" style="overflow-x: auto;">
            <a class="layui-btn" href="{:url('add')}">添加</a>
            <table class="layui-table" lay-size="sm">
                <colgroup>
                    <col class="w100">
                    <col>
                    <col class="w120">
                    <col class="w200">
                    <col class="w150">
                    <col class="w150">
                    <col class="w150">
                    <col class="w80">
                    <col class="w80">
                    <col class="w200">
                </colgroup>
                <thead>
                <tr>
                    <th class="tc">编号</th>
                    <th class="tc">事项内容</th>
                    <th class="tc">主负责人</th>
                    <th class="tc">相关人员</th>
                    <th class="tc">ECT(预计)</th>
                    <th class="tc">ACT(实际)</th>
                    <th class="tc">创建时间</th>
                    <th class="tc">创建人</th>
                    <th class="tc">状态</th>
                    <th class="tc">操作</th>
                </tr>
                </thead>
                <tbody>
                {foreach name="list" item="v"}
                <tr>
                    <td>{$v.task_no}</td>
                    <td class="hover-elem"><div class="ellipsis">{$v.title}</div></td>
                    <td>{:userId2Name($v['user_master'])}</td>
                    <td>{:userId2Name($v['user_support'])}</td>
                    <td>{$v.ect_time}</td>
                    <td>{$v.act_time}</td>
                    <td>{$v.create_time}</td>
                    <td>{$v.account.nickname}</td>
                    <td class="tc">
                        {if condition="$v.status eq 1"}
                        <span class="green">已完成</span>
                        {else/}
                        <span class="red">未完成</span>
                        {/if}
                    </td>
                    <td class="tc">
                        <button data-id="{$v.id}" class="layui-btn layui-btn-sm layui-btn-warm ml0" lay-submit lay-filter="Complete">快速完成</button>
                        <a href="{:url('edit', ['id' => $v.id])}" class="layui-btn layui-btn-normal layui-btn-sm">编辑</a>
                        <button data-id="{$v.id}" class="layui-btn layui-btn-sm layui-btn-danger ml0" lay-submit lay-filter="Delete">删除</button>
                    </td>
                </tr>
                {/foreach}
                </tbody>
            </table>
        </div>
        {$list->render()}
    </div>
</div>
<script>
    layui.use(['form', 'jquery', 'upload', 'laydate'], function(){
        let $ = layui.jquery,
            form = layui.form,
            upload = layui.upload,
            xmSelect = layui.xmSelect,
            laydate = layui.laydate;

        // 显示日期选择器
        laydate.render({
            elem: '#export_start_time',
            type: 'datetime'
        });
        laydate.render({
            elem: '#export_end_time',
            type: 'datetime'
        });

        // 显示日期选择器
        laydate.render({
            elem: '#export_start_time',
            type: 'datetime'
        });
        laydate.render({
            elem: '#export_end_time',
            type: 'datetime'
        });

        // 启用搜索
        // var xmSelect = layui.xmSelect; // 确保 layui.use 已加载 xmSelect
        let select = xmSelect.render({
            el: '#user_master',
            name: 'user_master',
            tips: '负责人',
            radio: true,
            clickClose: true,
            filterable: true,
            remoteSearch: true,   // 开启远程搜索
            remoteMethod: function(val, cb) {
                // val 是输入值
                // cb 是回调函数，用于返回数据
                $.get("{:url('Check/get_task_active_user')}", {search: val, selected: "{$user_master}"}, function(res){
                    // cb(res.data); // 返回数组 [{name:'苹果',value:'1'}, ...]
                    let result = JSON.parse(res).filter(function(item){
                        return item.name.indexOf(val) !== -1;
                    });
                    cb(result);
                });
            }
        });

        let select2 = xmSelect.render({
            el: '#create_id',
            name: 'create_id',
            tips: '创建人',
            radio: true,
            clickClose: true,
            filterable: true,
            remoteSearch: true,   // 开启远程搜索
            remoteMethod: function(val, cb) {
                // val 是输入值
                // cb 是回调函数，用于返回数据
                $.get("{:url('Check/get_task_active_admin')}", {search: val, selected: "{$create_id}"}, function(res){
                    // cb(res.data); // 返回数组 [{name:'苹果',value:'1'}, ...]
                    let result = JSON.parse(res).filter(function(item){
                        return item.name.indexOf(val) !== -1;
                    });
                    cb(result);
                });
            }
        });

        let tipIndex = null;
        let hideTimer = null;

        // 触发元素：移入显示
        $('.hover-elem').on('mouseenter', function(){
            let length = $(this).children().html().length;
            if (length >= 64) {
                clearTimeout(hideTimer);
                let that = this;

                // 已有就不重复创建
                if (tipIndex !== null) return;

                tipIndex = layer.tips(
                    '<div class="tip-content">' + $(this).children().html() + '</div>',
                    that,
                    {
                        tips: [3, '#333'],   // 朝向/颜色
                        time: 0,             // 0 = 不自动关闭
                        area: ['400px', 'auto'],
                        shade: 0,
                        anim: 5,
                        success: function(layero){
                            // 关键：在悬浮层本身也绑定进入/离开
                            layero.on('mouseenter', function(){
                                clearTimeout(hideTimer);
                            });
                            layero.on('mouseleave', function(){
                                hideTimer = setTimeout(function(){
                                    layer.close(tipIndex);
                                    tipIndex = null;
                                }, 150); // 150ms 缓冲，允许鼠标「跨过空隙」
                            });
                        }
                    }
                );
            }
        });

        // 触发元素：移出时不要立刻关，给点时间让鼠标移动到层上
        $('.hover-elem').on('mouseleave', function(){
            hideTimer = setTimeout(function(){
                if (tipIndex !== null){
                    layer.close(tipIndex);
                    tipIndex = null;
                }
            }, 150);
        });

        // 删除
        form.on('submit(Delete)', function(data){
            let text = $(this).text(),
                button = $(this),
                id = $(this).data('id');
            layer.confirm('确定删除吗？',{icon:3,closeBtn:0,title:false,btnAlign:'c'},function(){
                $('button').attr('disabled',true);
                button.text('请稍候...');
                axios.post("{:url('delete')}", {id:id})
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

        // 快速完成
        form.on('submit(Complete)', function(data){
            let text = $(this).text(),
                button = $(this),
                id = $(this).data('id');
            layer.confirm('确定要快速完成吗？',{icon:3,closeBtn:0,title:false,btnAlign:'c'},function(){
                $('button').attr('disabled',true);
                button.text('请稍候...');
                axios.post("{:url('complete')}", {id:id})
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
    });
</script>

{include file="public/footer" /}
