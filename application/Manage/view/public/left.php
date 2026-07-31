
    <!-- 侧边菜单 -->
    <div class="layui-side layui-side-menu" id="layui-side-menu">
        <div class="layui-side-scroll">
            <a class="layui-logo" layui-href="/Manage">
                <span><img src="" height="40"></span>
            </a>
          
            <ul class="layui-nav layui-nav-tree" lay-shrink="all" id="LAY-system-side-menu" lay-filter="layadmin-system-side-menu">
                <li data-name="Home" class="layui-nav-item layui-nav-itemed">
                    <a layui-href="/Manage/Index/index.html" lay-tips="控制台" lay-direction="2">
                        <i class="layui-icon layui-icon-home"></i>
                        <cite>控制台</cite>
                    </a>
                </li>
                <li data-name="Order" class="layui-nav-item">
                    <a layui-href="javascript:;" lay-tips="订单" lay-direction="2">
                        <i class="layui-icon iconfont icon-dingdan1"></i>
                        <cite>尾程</cite>
                    </a>
                    <dl class="layui-nav-child">
                        <dd><a layui-href="{:url('Order/index')}">尾程费用</a></dd>
                    </dl>
                </li>
                <li data-name="Inventory" class="layui-nav-item">
                    <a layui-href="javascript:;" lay-tips="库存" lay-direction="2">
                        <i class="layui-icon iconfont icon-kucun"></i>
                        <cite>库存</cite>
                    </a>
                    <dl class="layui-nav-child">
<!--                        <dd><a layui-href="{:url('Inventory/index')}">易仓批次库存</a></dd>-->
                        <dd><a layui-href="{:url('LcInventory/index')}">良仓批次库存</a></dd>
                        <dd><a layui-href="{:url('LeInventory/index')}">乐歌批次库存</a></dd>
                        <dd><a layui-href="{:url('WydInventory/index')}">无忧达批次库存</a></dd>
                        <dd><a layui-href="{:url('EdaInventory/index')}">易达云批次库存</a></dd>
                    </dl>
                </li>
                <li data-name="Finance" class="layui-nav-item">
                    <a layui-href="javascript:;" lay-tips="财务" lay-direction="2">
                        <i class="layui-icon iconfont icon-caiwu1"></i>
                        <cite>财务</cite>
                    </a>
                    <dl class="layui-nav-child">
                        <dd><a layui-href="{:url('Finance/report')}">财务报表</a></dd>
                        <dd><a layui-href="{:url('FinanceOperation/index')}">营运费用</a></dd>
                        <dd><a layui-href="{:url('Finance/order_statistics')}">订单统计</a></dd>
                        <dd><a layui-href="{:url('Finance/warehouse_tail')}">尾程差异</a></dd>
                        <dd><a layui-href="{:url('FinanceWildberries/index')}">Wildberries</a></dd>
                        <dd><a layui-href="{:url('FinanceTemu/tail')}">Temu面单</a></dd>
                        <dd><a layui-href="{:url('Finance/wayfair_core')}">Wayfair订单</a></dd>
                        <dd><a layui-href="{:url('Finance/obsolete')}">滞销产品</a></dd>
                        <dd><a layui-href="{:url('SkuRelation/index')}">SKU映射</a></dd>
                        <dd><a layui-href="{:url('WarehouseTail/index')}">海外仓尾程</a></dd>
                    </dl>
                </li>
                <li data-name="SkuReport" class="layui-nav-item">
                    <a layui-href="javascript:;" lay-tips="BI" lay-direction="2">
                        <i class="layui-icon iconfont icon-icon"></i>
                        <cite>报表</cite>
                    </a>
                    <dl class="layui-nav-child">
                        <dd><a layui-href="{:url('SkuReport/index')}">Sku报表</a></dd>
                        <dd><a layui-href="{:url('SkuReport/category')}">类目报表</a></dd>
                        <dd><a layui-href="{:url('SkuReport/daily')}">日销报表</a></dd>
                        <dd><a layui-href="{:url('SkuReport/store')}">日库存报表</a></dd>
                        <dd><a layui-href="{:url('SkuReport/stock_sale')}">库存库龄月销量报表</a></dd>
                        <dd><a layui-href="{:url('SkuReport/growth')}">销量月增长报表</a></dd>
                        <dd><a layui-href="{:url('SkuReport/wayfair')}">各平台销量报表</a></dd>
                        <dd><a layui-href="{:url('SkuReport/amount_paid')}">各平台销售报表</a></dd>
                        <dd><a layui-href="{:url('SkuReport/wayfair_only')}">Wayfair销量报表-陈瑜</a></dd>
                        <dd><a layui-href="{:url('SkuReport/platform_sale')}">各平台销量销售额排行</a></dd>
                        <dd><a layui-href="{:url('SkuReport/four_zone')}">SKU发货四区率</a></dd>
                        <dd><a layui-href="{:url('SkuReport/four_warehouse')}">SKU备货四仓率</a></dd>
                        <dd><a layui-href="{:url('Echarts/index')}">美国各州销量热力图</a></dd>
                        <dd><a layui-href="{:url('Echarts/listing')}">Listing排名表</a></dd>
                        <dd><a layui-href="{:url('Echarts/listing_group')}">Listing产品组</a></dd>
                        <dd><a layui-href="{:url('SkuReport/warehouse_tail')}">海外仓尾程</a></dd>
                        <dd><a layui-href="{:url('SkuReport/warehouse_rent')}">海外仓仓租</a></dd>
                        <dd><a layui-href="{:url('FinanceReport/index')}">财报利润柱状图</a></dd>
                        <dd><a layui-href="{:url('FinanceReport/platform_profit')}">财报利润多平台柱状图</a></dd>
                        <dd><a layui-href="{:url('FinanceReport/index_group_multi')}">品类利润柱状图</a></dd>
                        <dd><a layui-href="{:url('FinanceReport/pie_chart')}">财报利润饼状图</a></dd>
                        <dd><a layui-href="{:url('SkuReport/sku_sale_qty')}">销量对比柱状图</a></dd>
                        <dd><a layui-href="{:url('SkuReport/sku_sale_profit')}">利润对比柱状图</a></dd>
                        <dd><a layui-href="{:url('SkuReport/warehouse_fbm')}">FBM仓租排行榜</a></dd>
                    </dl>
                </li>
                <li data-name="Product" class="layui-nav-item">
                    <a layui-href="javascript:;" lay-tips="产品" lay-direction="2">
                        <i class="layui-icon iconfont icon-chanpin2"></i>
                        <cite>产品</cite>
                    </a>
                    <dl class="layui-nav-child">
                        <dd><a layui-href="{:url('Product/index')}">易仓产品列表</a></dd>
                        <dd><a layui-href="{:url('Product/lc')}">良仓产品列表</a></dd>
                        <dd><a layui-href="{:url('Product/le')}">乐歌产品列表</a></dd>
                        <dd><a layui-href="{:url('Product/wyd')}">无忧达产品列表</a></dd>
                        <dd><a layui-href="{:url('Product/eda')}">易达云产品列表</a></dd>
                    </dl>
                </li>
                <li data-name="Product" class="layui-nav-item">
                    <a layui-href="javascript:;" lay-tips="其他" lay-direction="2">
                        <i class="layui-icon iconfont icon-qita"></i>
                        <cite>其他</cite>
                    </a>
                    <dl class="layui-nav-child">
                        <dd><a layui-href="{:url('Task/index')}">任务跟踪</a></dd>
                        <dd><a layui-href="{:url('Product/performance')}">产品表现</a></dd>
                        <dd><a layui-href="{:url('WarehouseRent/estimate')}">仓储预估</a></dd>
                    </dl>
                </li>
                <li data-name="Storage" class="layui-nav-item">
                    <a layui-href="javascript:;" lay-tips="基础" lay-direction="2">
                        <i class="layui-icon iconfont icon-jichugongneng"></i>
                        <cite>基础</cite>
                    </a>
                    <dl class="layui-nav-child">
                        <dd><a layui-href="{:url('Storage/index')}">基础仓库</a></dd>
                        <dd><a layui-href="{:url('StorageArea/index')}">常用仓库</a></dd>
                        <dd><a layui-href="{:url('StorageZone/index')}">邮编Zone</a></dd>
                        <dd><a layui-href="{:url('StorageOutbound/index')}">出库费用</a></dd>
                        <dd><a layui-href="{:url('StorageBase/index')}">基础费用</a></dd>
                        <dd><a layui-href="{:url('StorageAhs/index')}">AHS费用</a></dd>
                        <dd><a layui-href="{:url('StorageDas/index')}">偏远地区</a></dd>
                    </dl>
                </li>
                {if condition="$user.super eq 1 and $user.id eq 1"}
                <li data-name="Site" class="layui-nav-item">
                    <a layui-href="javascript:;" lay-tips="设置" lay-direction="2">
                        <i class="layui-icon layui-icon-set"></i>
                        <cite>设置</cite>
                    </a>
                    <dl class="layui-nav-child">
                        <dd><a layui-href="{:url('Param/storage')}">参数配置</a></dd>
                        <!-- <dd><a layui-href="{:url('Mail/index')}">邮件设置</a></dd> -->
                        <dd data-name="info">
                            <a layui-href="javascript:;">管理设置</a>
                            <dl class="layui-nav-child">
                                <dd><a layui-href="{:url('Admin/index')}">管理员</a></dd>
                                <dd><a layui-href="{:url('Admin/role')}">角色</a></dd>
                                {if condition="$user.manage eq 1"}
                                <dd><a layui-href="{:url('Admin/node')}">节点</a></dd>
                                {/if}
                            </dl>
                        </dd>
                    </dl>
                </li>
                {/if}
            </ul>
        </div>
    </div>
    <script type="text/javascript">
    layui.use(['jquery'], function(){
        var $ = layui.jquery;

        {if condition="$user.super"}
        $("#layui-side-menu dl").each(function(){
            let arr = [],
                a_tag = $(this).find('a'),
                unique_arr = [],
                controller_str = '';
            a_tag.each(function(){
                arr.push($(this).attr('layui-href').split('/')[2]);
            });
            unique_arr = $.grep($.unique(arr), function(item) {
                return item !== undefined && item !== null && item !== '';
            });

            controller_str = unique_arr.join(',');
            let url = "/Manage/Auth/index/controller/" + controller_str + ".html";
            $(this).append('<dd class=""><a layui-href="' + url + '">权限列表</a></dd>');
        });
        {/if}

        if ('{$userMenu}') {
            $("#layui-side-menu").html('{$userMenu}');
            $(".layui-nav-bar").remove();
        }

        $("#layui-side-menu a").click(function(){
            $('dd').removeClass('layui-this');
            if ($(this).attr('layui-href') != 'javascript:;') {
                $(this).parent('dd').addClass('layui-this');
            }
            var html = $("#layui-side-menu").html(),
                href = $(this).attr('layui-href');

            $.ajax({
                type:'POST',url:"{:url('Index/initMenu')}",data:{"info": html},dataType:'json',
                success:function(data){
                    if(data.code == 1){
                        location.href = href;
                    }
                }
            });
        });
    });
    </script>
