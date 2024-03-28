
{include file="public/header" /}

<!-- 主体内容 -->
<div class="layui-body" id="LAY_app_body">
    <div class="right">
        <div class="title">乐歌仓储费</div>
        <form class="layui-form search-form" method="get">
            <div class="layui-inline w200">
                <input type="text" class="layui-input" name="keyword" value="{$keyword}" placeholder="SKU/各种单号">
            </div>
            <div class="layui-inline w120">
                <select name="warehouseCode" lay-verify="">
                    <option value="">仓库代码</option>
                    <option value="PAW" {if condition="$warehouseCode eq 'PAW'"}selected{/if}>PAW</option>
                    <option value="CAP" {if condition="$warehouseCode eq 'CAP'"}selected{/if}>CAP</option>
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
        </form>

        <div class="layui-form">
            <button type="button" class="layui-btn  layui-btn-disabled" id="excel">导入</button>
            <table class="layui-table" lay-size="sm">
                <colgroup>
                    <col width="50">
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
                    <col width="60">
                </colgroup>
                <thead>
                <tr>
                    <th class="tc">
                        <input type="checkbox" lay-skin="primary" id="YanNanQiu_checkall" lay-filter="YanNanQiu_checkall">
                    </th>
                    <th>ID</th>
                    <th>入库单据</th>
                    <th>仓库代码</th>
                    <th>乐仓SKU</th>
                    <th>品名</th>
                    <th>中文品名</th>
                    <th>入库时间</th>
                    <th class="tc">长(INCH)</th>
                    <th class="tc">宽(INCH)</th>
                    <th class="tc">高(INCH)</th>
                    <th class="tc">重量(LBS)</th>
                    <th class="tc">可用数量</th>
                    <th class="red tc">库龄</th>
                    <th>计费日期</th>
                    <th class="tc">体积</th>
                    <th class="tc">合计</th>
                    <th>状态</th>
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
                    <td>{$v.businessNo}</td>
                    <td>{$v.warehouseCode}</td>
                    <td>{$v.lecangsCode}</td>
                    <td>{$v.enName}</td>
                    <td>{$v.cnName}</td>
                    <td>{$v.warehouseDate}</td>
                    <td class="tr">{$v.wmsLength}</td>
                    <td class="tr">{$v.wmsWidth}</td>
                    <td class="tr">{$v.wmsHeight}</td>
                    <td class="tr">{$v.wmsWeight}</td>
                    <td class="tr">{$v.goodsNum}</td>
                    <td class="red tr">{$v.inventoryAge}</td>
                    <td>{$v.created_date}</td>
                    <td class="tr">{$v.volume}</td>
                    <td class="tr">{$v.price}</td>
                    <td>
                        {if condition="$v.is_finished eq 0"}
                            <p class="red">未核算</p>
                        {elseif condition="$v.is_finished eq 1"/}
                            <p class="grey">已核算</p>
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
    layui.use(['form', 'jquery', 'upload'], function(){
        let $ = layui.jquery,
            form = layui.form,
            upload = layui.upload;

        // 上传
        let uploadInst = upload.render({
            elem: '#excel' //绑定元素
            ,url: '/Manage/Upload/file_upload' //上传接口
            ,exts: 'xls|xlsx'
            ,done: function(res){
                //上传完毕回调
                console.log(res.data);
                location.href = "/Manage/LeInventory/import/filename/" + res.data;
            }
            ,error: function(){
                //请求异常回调
            }
        });

    });
</script>

{include file="public/footer" /}
