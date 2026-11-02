
{include file="public/header" /}

<!-- 主体内容 -->
<div class="layui-body" id="LAY_app_body">
    <div class="right">
        <div class="title">尾程试算列表</div>
        <form class="layui-form search-form" method="get">
            <div class="layui-inline w200">
                <input type="text" class="layui-input" name="keyword" value="{$keyword}" placeholder="Zone">
            </div>
            <div class="layui-inline">
                <button class="layui-btn" lay-submit lay-filter="Search"><i class="layui-icon">&#xe615;</i> 查询</button>
            </div>
        </form>

        <div class="layui-form">
            <a class="layui-btn" href="{:url('cost_estimate_wyd_add')}">添加</a>
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
                    <col class="w500">
                </colgroup>
                <thead>
                <tr>
                    <th>id</th>
                    <th>warehouse</th>
                    <th>logisticsProductsCode</th>
                    <th>logisticsProviderCode</th>
                    <th>countryCode</th>
                    <th>province</th>
                    <th>city</th>
                    <th>postCode</th>
                    <th>size</th>
                    <th>needSignature</th>
                    <th>deliveryStyle</th>
                    <th>created_time</th>
                    <th>wrapperList</th>
                </tr>
                </thead>
                <tbody>
                {foreach name="list" item="v"}
                <tr>
                    <td class="tr">{$v.id}</td>
                    <td>{$v.warehouse}</td>
                    <td>{$v.logisticsProductsCode}</td>
                    <td>{$v.logisticsProviderCode}</td>
                    <td>{$v.countryCode}</td>
                    <td>{$v.province}</td>
                    <td>{$v.city}</td>
                    <td>{$v.postCode}</td>
                    <td>{$v.length}*{$v.height}*{$v.width}*{$v.weight}</td>
                    <td class="tr">{$v.needSignature}</td>
                    <td class="tr">{$v.deliveryStyle}</td>
                    <td class="tr">{$v.created_time}</td>
                    <td>
                        <table>
                        {foreach name="$v.wrapperList|json_decode_sort_asc", item="vi"}
                            <tbody {if condition="$vi.totalAmount elt 10"}class="green"{/if}>
                                <td>{$vi.logisticsProductsCode}</td>
                                <td>{$vi.logisticsProviderName}</td>
                                <td>{$vi.currency}</td>
                                <td>{$vi.totalAmount}</td>
                            </tbody>
                        {/foreach}
                        </table>
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
    layui.use(['form', 'jquery'], function(){
        let $ = layui.jquery,
            form = layui.form;

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
    });
</script>

{include file="public/footer" /}
