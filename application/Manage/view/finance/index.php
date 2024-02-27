
{include file="public/header" /}

<!-- 主体内容 -->
<div class="layui-body" id="LAY_app_body">
    <div class="right">
        <a href="{:url('report')}" class="layui-btn layui-btn-danger layui-btn-sm fr"><i class="layui-icon">&#xe603;</i>返回上一页</a>
        <div class="title">导入表格列表</div>
        <form class="layui-form search-form" method="get">
            <div class="layui-inline w200">
                <input type="text" class="layui-input" name="keyword" value="{$keyword}" placeholder="原始单号">
            </div>
            <div class="layui-inline">
                <button class="layui-btn" lay-submit lay-filter="Search"><i class="layui-icon">&#xe615;</i> 查询</button>
            </div>
            <div class="layui-inline">
                <a class="layui-btn layui-btn-normal" href="{:url('index')}"><i class="layui-icon">&#xe669;</i> 重置</a>
            </div>
        </form>

        <div class="layui-form">
            <button type="button" class="layui-btn  layui-btn-normal" id="excel">导入</button>
            <table class="layui-table" lay-size="sm">
                <colgroup>
                    <col width="50">
                    <col width="50">
                    <col>
                    <col width="140">
                    <col width="80">
                    <col width="80">
                </colgroup>
                <thead>
                <tr>
                    <th class="tc">
                        <input type="checkbox" lay-skin="primary" id="YanNanQiu_checkall" lay-filter="YanNanQiu_checkall">
                    </th>
                    <th class="tc">ID</th>
                    <th>导入表格名</th>
                    <th>导入时间</th>
                    <th class="tc">状态</th>
                    <th class="tc">操作</th>
                </tr>
                </thead>
                <tbody>
                {foreach name="list" item="v"}
                <tr>
                    <td class="tc">
                        <div class="YanNanQiu_Checkbox">
                            <input type="checkbox" name="id[]" lay-skin="primary" lay-filter="imgbox" class="YanNanQiu_imgId" value="{$v.id}">
                        </div>
                    </td>
                    <td class="tc">{$v.id}</td>
                    <td>{$v.table_name}</td>
                    <td>{$v.created_at}</td>
                    <td class="tc">
                        {if condition="$v.is_notify eq 1"}
                        <span class="green">已同步</span>
                        {else/}
                        <span class="red">未同步</span>
                        {/if}
                    </td>
                    <td class="tc">
                        <a href="{:url('order', ['id' => $v.id])}" class="layui-btn layui-btn layui-btn-sm">查看</a>
                    </td>
                </tr>
                {/foreach}
                </tbody>
            </table>
            {$list->render()}
        </div>

    </div>
</div>
<script>
    layui.use(['form', 'jquery', 'upload', 'laydate'], function(){
        let $ = layui.jquery,
            form = layui.form,
            upload = layui.upload;

        // 上传
        let uploadInst = upload.render({
            elem: '#excel' //绑定元素
            ,url: '/manage/upload/file_upload' //上传接口
            ,exts: 'xls|xlsx|csv'
            ,multiple: true
            ,done: function(res){
                //上传完毕回调
                console.log(res.data);
                location.href = "/Manage/Finance/import/rid/{$rid}/filename/" + res.data + "/origin/" + res.origin;
            }
            ,error: function(){
                //请求异常回调
            }
        });
    });
</script>

{include file="public/footer" /}
