
{include file="public/header" /}

<style>
    .calcuRes {cursor: pointer;}
</style>
<!-- 主体内容 -->
<div class="layui-body" id="LAY_app_body">
    <div class="right">
        <div class="title">尾程列表</div>
        <form class="layui-form search-form" method="get">
            <div class="layui-inline w200">
                <input type="text" class="layui-input" name="keyword" value="{$keyword}" placeholder="参考/销售/系统单号">
            </div>
            <div class="layui-inline w120">
                <select name="platform" lay-verify="">
                    <option value="">平台类型</option>
                    <option value="amazon" {if condition="$platform eq 'amazon'"}selected{/if}>amazon</option>
                    <option value="wayfairnew" {if condition="$platform eq 'wayfairnew'"}selected{/if}>wayfairnew</option>
                    <option value="walmart" {if condition="$platform eq 'walmart'"}selected{/if}>walmart</option>
                    <option value="shoplazza" {if condition="$platform eq 'shoplazza'"}selected{/if}>shoplazza</option>
                    <option value="shein" {if condition="$platform eq 'shein'"}selected{/if}>shein</option>
                    <option value="ebay" {if condition="$platform eq 'ebay'"}selected{/if}>ebay</option>
                </select>
            </div>
            <div class="layui-inline w120">
                <select name="status" lay-verify="">
                    <option value="">状态</option>
                    <option value="0" {if condition="$status eq '0'"}selected{/if}>已废弃</option>
                    <option value="1" {if condition="$status eq 1"}selected{/if}>未付款</option>
                    <option value="2" {if condition="$status eq 2"}selected{/if}>待审核</option>
                    <option value="3" {if condition="$status eq 3"}selected{/if}>待发货</option>
                    <option value="4" {if condition="$status eq 4"}selected{/if}>已发货</option>
                    <option value="5" {if condition="$status eq 5"}selected{/if}>冻结中</option>
                    <option value="6" {if condition="$status eq 6"}selected{/if}>缺货</option>
                    <option value="7" {if condition="$status eq 7"}selected{/if}>问题件</option>
                    <option value="8" {if condition="$status eq 8"}selected{/if}>线下单</option>
                    <option value="100" {if condition="$status eq 100"}selected{/if}>未审核</option>
                    <option value="101" {if condition="$status eq 101"}selected{/if}>审不过</option>
                    <option value="102" {if condition="$status eq 102"}selected{/if}>废弃订单</option>
                </select>
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
<!--            <a class="layui-btn" href="{:url('add')}">添加</a>-->
            <a class="layui-btn layui-btn-normal" lay-submit lay-filter="Calculate">测算</a>
            <a class="layui-btn layui-btn-normal" lay-submit lay-filter="Add">新增</a>
            <button type="button" class="layui-btn  layui-btn-normal" id="excel">导入</button>
            <a class="layui-btn layui-btn-normal" lay-submit lay-filter="Update">批量更新</a>
            <a class="layui-btn layui-btn-normal" lay-submit lay-filter="Audit">批量审核</a>
            <table class="layui-table" lay-size="sm">
                <colgroup>
                    <col width="50">
                    <col>
                    <col width="150">
                    <col width="150">
                    <col width="150">
                    <col width="100">
                    <col width="120">
                    <col>
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
                    <col>
                    <col width="140">
                    <col width="80">
                </colgroup>
                <thead>
                <tr>
                    <th class="tc">
                        <input type="checkbox" lay-skin="primary" id="YanNanQiu_checkall" lay-filter="YanNanQiu_checkall">
                    </th>
                    <th class="tc">ID</th>
                    <th class="tc">Payment单号</th>
                    <th class="tc">易仓单号</th>
                    <th class="tc">海外仓单号</th>
                    <th class="tc">跟踪号</th>
                    <th class="tc">仓库代码</th>
                    <th class="tc">平台</th>
                    <th class="tc">Sku</th>
                    <th class="tc">计费重</th>
                    <th class="tc">邮编</th>
                    <th class="tc">Zone</th>
                    <th class="tc">出库</th>
                    <th class="tc">基础</th>
                    <th class="tc">AHS</th>
                    <th class="tc">偏远</th>
                    <th class="tc">住宅</th>
                    <th class="tc">旺季</th>
                    <th class="tc">燃油</th>
                    <th class="tc">佣金</th>
                    <th class="tc">总计</th>
                    <th class="tc">发货时间</th>
                    <th class="tc">状态</th>
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
                    <td>{$v.refNo}</td>
                    <td>{$v.saleOrderCode}</td>
                    <td>{$v.shippingOrderCode}</td>
                    <td>{$v.shippingMethodNo}</td>
                    <td>{$v.warehouseCode}</td>
                    <td>{$v.platform}</td>
                    <td>{$v.details.0.product.productSku}</td>
                    <td class="tr">{$v.charged_weight}</td>
                    <td>{$v.postalFormat}</td>
                    <td class="tr">{$v.zoneFormat}</td>
                    <td class="tr">{$v.outbound}</td>
                    <td class="tr">{$v.base}</td>
                    <td class="tr">{$v.ahs}</td>
                    <td class="tr">{$v.das}</td>
                    <td class="tr">{$v.rdcFee}</td>
                    <td class="tr">{$v.ahsds}+{$v.drdcFee}</td>
                    <td class="tr">{$v.fuelCost}</td>
                    <td class="tr">{$v.commission}</td>
                    <td class="tr calcuRes" data-info="{$v.calcuInfo}">{$v.calcuRes}</td>
                    <td>{$v.dateWarehouseShipping}</td>
                    <td class="tc">
                        {if condition="$v.status eq 0"}
                            <p class="grey">已废弃</p>
                        {elseif condition="$v.status eq 1"/}
                            <p class="orange">未付款</p>
                        {elseif condition="$v.status eq 2" /}
                            <p class="blue">待审核</p>
                        {elseif condition="$v.status eq 3" /}
                            <p class="blue">待发货</p>
                        {elseif condition="$v.status eq 4" /}
                            <p class="green">已发货</p>
                        {elseif condition="$v.status eq 5" /}
                            <p class="orange">冻结中</p>
                        {elseif condition="$v.status eq 6" /}
                            <p class="red">缺货</p>
                        {elseif condition="$v.status eq 7" /}
                            <p class="red">问题件</p>
                        {elseif condition="$v.status eq 8" /}
                            <p class="red">未付款</p>
                        {elseif condition="$v.status eq 100" /}
                            <p class="grey">线下单</p>
                        {elseif condition="$v.status eq 101" /}
                            <p class="red">审核不通过</p>
                        {elseif condition="$v.status eq 102" /}
                            <p class="grey">废弃订单</p>
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
            ,url: '/Manage/Upload/file_upload' //上传接口
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

        // 新增
        form.on('submit(Add)', function(data){
            let text = $(this).text(),
                button = $(this);
            layer.prompt({title: '请输入易仓单号'}, function(value){
                $('button').attr('disabled',true);
                button.text('请稍候...');
                axios.post("{:url('add')}", {data:value})
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
