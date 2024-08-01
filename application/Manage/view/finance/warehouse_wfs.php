
{include file="public/header" /}

<style>
    .total {padding: 0 10px}
</style>
<!-- 主体内容 -->
<div class="layui-body" id="LAY_app_body">
    <div class="right">
        <a href="{:session('back_url', '', 'manage')}" class="layui-btn layui-btn-danger layui-btn-sm fr"><i class="layui-icon">&#xe603;</i>返回上一页</a>
        <div class="title">WFS仓储费</div>
        <form class="layui-form search-form" method="get">
            <div class="layui-inline w200">
                <input type="text" class="layui-input" name="keyword" value="{$keyword}" placeholder="SKU">
            </div>
            <div class="layui-inline w100">
                <input type="text" class="layui-input" name="page_num" value="{$page_num}" placeholder="每页条数">
            </div>
            <div class="layui-inline">
                <button class="layui-btn" lay-submit lay-filter="Search"><i class="layui-icon">&#xe615;</i> 查询</button>
            </div>
        </form>

        <div class="layui-form">
            <div class="layui-input-inline w180">
                <select name="user_account" id="user_account">
                    <option value="">请选择店铺</option>
                    <option value="JG_Direct">JG_Direct</option>
                    <option value="WAL_Carajali_US">WAL_Carajali_US</option>
                </select>
            </div>
            <button type="button" class="layui-btn  layui-btn-normal" id="excel">导入</button>
            <button data-id="{$report_id}" class="layui-btn layui-btn-danger ml0" lay-submit lay-filter="Detele">清空</button>
            <span class="total">合计：{$total}</span>
            <table class="layui-table" lay-size="sm">
                <colgroup>
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
                    <col>
                    <col>
                    <col>
                    <col>
                    <col>
                    <col>
                </colgroup>
                <thead>
                <tr>
                    <th>ID</th>
                    <th>User Account</th>
                    <th>Partner GTIN</th>
                    <th>Vendor SKU</th>
                    <th>Walmart Item ID</th>
                    <th>Length</th>
                    <th>Width</th>
                    <th>Height</th>
                    <th>Volume</th>
                    <th>Weight</th>
                    <th>Standard Daily</th>
                    <th>Peak Daily</th>
                    <th>Long-term Daily</th>
                    <th>Average Units</th>
                    <th>Ending Units</th>
                    <th>Total</th>
                </tr>
                </thead>
                <tbody>
                {foreach name="list" item="v"}
                <tr>
                    <td>{$v.id}</td>
                    <td>{$v.user_account}</td>
                    <td>{$v.partner_gtin}</td>
                    <td>{$v.vendor_sku}</td>
                    <td>{$v.walmart_item_id}</td>
                    <td class="tr">{$v.length}</td>
                    <td class="tr">{$v.width}</td>
                    <td class="tr">{$v.height}</td>
                    <td class="tr">{$v.volume}</td>
                    <td class="tr">{$v.weight}</td>
                    <td class="tr">{$v.standard_daily_storage}</td>
                    <td class="tr">{$v.peak_daily_storage}</td>
                    <td class="tr">{$v.long_term_daily_storage}</td>
                    <td class="tr">{$v.average}</td>
                    <td class="tr">{$v.ending}</td>
                    <td class="tr">{$v.total}</td>
                </tr>
                {/foreach}
                </tbody>
            </table>
            {$list->render()}
        </div>

    </div>
</div>
<script>
    layui.use(['form', 'jquery', 'upload'], function(){
        let $ = layui.jquery,
            form = layui.form,
            upload = layui.upload;

        // 上传
        let uploadInst = upload.render({
            elem: '#excel' //绑定元素
            ,url: '/Manage/upload/warehouse_wfs_upload' //上传接口
            ,exts: 'xls|xlsx|csv'
            ,data: {
                user_account: function(){
                    return $("#user_account").val();
                }
            }
            ,multiple: true
            ,before: function (obj){
                layer.load(1);
            }
            ,done: function(res){
                //上传完毕回调
                if (res.code === 1) {
                    location.href = "/Manage/Finance/warehouse_wfs_import/id/{$report_id}/filename/" + res.data + "/origin/" + res.origin + "/user_account/" + res.user_account;
                } else {
                    layer.alert(res.msg,{icon:2,closeBtn:0,title:false,btnAlign:'c'},function(){
                        layer.closeAll();
                    });
                }
            }
            ,error: function(){
                //请求异常回调
            }
        });

        // 删除
        form.on('submit(Detele)', function(data){
            var text = $(this).text(),
                button = $(this),
                id = $(this).data('id');
            layer.confirm('确定清空列表吗？',{icon:3,closeBtn:0,title:false,btnAlign:'c'},function(){
                $('button').attr('disabled',true);
                button.text('请稍候...');
                axios.post("{:url('warehouse_wfs_empty')}", {id:id})
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
    });
</script>

{include file="public/footer" /}
