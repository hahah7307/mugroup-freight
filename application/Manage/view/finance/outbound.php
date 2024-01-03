
{include file="public/header" /}

<style>
    .calcuRes {cursor: pointer;}
</style>
<!-- 主体内容 -->
<div class="layui-body" id="LAY_app_body">
    <div class="right">
        <div class="title">出库明细列表</div>
        <form class="layui-form search-form" method="get">
            <div class="layui-inline w200">
                <input type="text" class="layui-input" name="keyword" value="{$keyword}" placeholder="参考/销售/系统单号">
            </div>
            <div class="layui-inline w100">
                <input type="text" class="layui-input" name="page_num" value="{$page_num}" placeholder="每页条数">
            </div>
            <div class="layui-inline">
                <button class="layui-btn" lay-submit lay-filter="Search"><i class="layui-icon">&#xe615;</i> 查询</button>
            </div>
            <div class="layui-inline">
                <a class="layui-btn layui-btn-normal" href="{:url('index')}"><i class="layui-icon">&#xe669;</i> 重置</a>
            </div>
            <div class="layui-input-inline">
                <input type="text" class="layui-input" id="export_start_time" name="start_time" value="" placeholder="开始时间">
            </div>
            <div class="layui-input-inline">
                <input type="text" class="layui-input" id="export_end_time" name="end_time" value="" placeholder="结束时间">
            </div>
            <div class="layui-inline">
                <a class="layui-btn layui-btn-normal" lay-submit lay-filter="Export"><i class="layui-icon">&#xe621;</i> 导出</a>
            </div>
        </form>

        <div class="layui-form">
            <table class="layui-table" lay-size="sm">
                <colgroup>
                    <col width="50">
                    <col>
                    <col width="150">
                    <col width="150">
                    <col width="150">
                    <col width="120">
                    <col width="100">
                    <col>
                    <col width="60">
                    <col>
                    <col>
                    <col>
                    <col>
                    <col>
                    <col>
                    <col>
                    <col>
                    <col>
                    <col>
                    <col width="140">
                    <col width="80">
                </colgroup>
                <thead>
                <tr>
                    <th class="tc">
                        <input type="checkbox" lay-skin="primary" id="YanNanQiu_checkall" lay-filter="YanNanQiu_checkall">
                    </th>
                    <th>ID</th>
                    <th>参考单号</th>
                    <th>销售单号</th>
                    <th>系统单号</th>
                    <th>仓库单号</th>
                    <th>仓库代码</th>
                    <th>平台</th>
                    <th>计费重</th>
                    <th>邮编</th>
                    <th>Zone</th>
                    <th>出库</th>
                    <th>基础</th>
                    <th>AHS</th>
                    <th>偏远</th>
                    <th>住宅</th>
                    <th>旺季</th>
                    <th>燃油</th>
                    <th>总计</th>
                    <th>创建时间</th>
                    <th class="tc">审核状态</th>
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
                    <td>{$v.id}</td>
                    <td>{$v.order_details.refNo}</td>
                    <td>{$v.order_details.saleOrderCode}</td>
                    <td>{$v.order_details.sysOrderCode}</td>
                    <td>{$v.order_details.warehouseOrderCode}</td>
                    <td>{$v.order_details.warehouseCode}</td>
                    <td>{$v.order_details.platform}</td>
                    <td>{$v.order_details.charged_weight}</td>
                    <td>{$v.order_details.postalFormat}</td>
                    <td>{$v.order_details.zoneFormat}</td>
                    <td>{$v.order_details.outbound}</td>
                    <td>{$v.order_details.base}</td>
                    <td>{$v.order_details.ahs}</td>
                    <td>{$v.order_details.das}</td>
                    <td>{$v.order_details.rdcFee}</td>
                    <td>{$v.order_details.ahsds}+{$v.order_details.drdcFee}</td>
                    <td>{$v.order_details.fuelCost}</td>
                    <td class="calcuRes" data-info="{$v.order_details.calcuInfo}">{$v.order_details.calcuRes}</td>
                    <td>{$v.order_details.createdDate}</td>
                    <td class="tc">
                        {if condition="$v.order_details.calcu_state eq 1"}
                            <p class="blue">待审核</p>
                        {elseif condition="$v.order_details.calcu_state eq 2" /}
                            <p class="green">通过</p>
                        {elseif condition="$v.order_details.calcu_state eq 3" /}
                            <p class="red">未通过</p>
                        {/if}
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
            upload = layui.upload,
            laydate = layui.laydate;

        // 上传
        let uploadInst = upload.render({
            elem: '#excel' //绑定元素
            ,url: '/manage/upload/file_upload' //上传接口
            ,exts: 'xls|xlsx'
            ,done: function(res){
                //上传完毕回调
                console.log(res.data);
                location.href = "/Manage/Order/import/filename/" + res.data;
            }
            ,error: function(){
                //请求异常回调
            }
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

        // 导出
        form.on('submit(Export)', function(data){
            let start_time = data.field.start_time,
                end_time = data.field.end_time;

            location.href = "{:url('Order/export')}" + "?start_time=" + start_time + "&end_time=" + end_time;
            return false;
        });

        // 状态
        form.on('switch(formLock)', function(data){
            $('button').attr('disabled',true);
            axios.post("{:url('status')}", {id:data.value,type:'look'})
                .then(function (response) {
                    let res = response.data;
                    if (res.code === 0) {
                        layer.alert(data.msg,{icon:2,closeBtn:0,title:false,btnAlign:'c'},function(){
                            location.reload();
                        });
                    }
                })
                .catch(function (error) {
                    console.log(error);
                });
            return false;
        });

        // 测算
        form.on('submit(Calculate)', function(data){
            let text = $(this).text(),
                button = $(this);
            layer.confirm('确定测算吗？',{icon:3,closeBtn:0,title:false,btnAlign:'c'},function(){
                $('button').attr('disabled',true);
                button.text('请稍候...');
                axios.post("{:url('calculate')}", {id:data.field})
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

        // 批量更新
        form.on('submit(Update)', function(data){
            let text = $(this).text(),
                button = $(this);
            layer.confirm('确定更新吗？',{icon:3,closeBtn:0,title:false,btnAlign:'c'},function(){
                $('button').attr('disabled',true);
                button.text('请稍候...');
                layer.load(2);
                axios.post("{:url('update')}", {id:data.field})
                    .then(function (response) {
                        let res = response.data;
                        if (res.code === 1) {
                            layer.alert(res.msg,{icon:1,closeBtn:0,title:false,btnAlign:'c',},function(){
                                layer.closeAll();
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

        // 批量审核
        form.on('submit(Audit)', function(data){
            let text = $(this).text(),
                button = $(this);
            $('button').attr('disabled',true);
            button.text('请稍候...');
            layer.open({
                content: '请选择审核结果？',
                icon: 3,
                btnAlign : 'c',
                btn: ['通过', '未通过'],
                title: false,
                yes: function(index) {
                    axios.post("{:url('auditYes')}", {id:data.field})
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
                },
                btn2: function (index) {
                    axios.post("{:url('auditNo')}", {id:data.field})
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
                },
                cancel: function(index) {
                    $('button').attr('disabled',false);
                    button.text('批量审核');
                }
            });
            return false;
        });

        // 显示费用详情特效
        $(".calcuRes").click(function(){
            let info = $(this).data('info');
            layer.alert(info,{
                title: "费用详情",
                icon: 7,
                area: ['500px', '180px'],
                btn: ['关闭'],
                btnAlign: 'c'
            });
        });
    });
</script>

{include file="public/footer" /}
