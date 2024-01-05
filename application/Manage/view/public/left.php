
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
                <li data-name="Storage" class="layui-nav-item">
                    <a layui-href="javascript:;" lay-tips="订单" lay-direction="2">
                        <i class="layui-icon iconfont icon-dingdan1"></i>
                        <cite>尾程</cite>
                    </a>
                    <dl class="layui-nav-child">
                        <dd><a layui-href="{:url('Order/index')}">尾程费用</a></dd>
                        <dd><a layui-href="{:url('Param/storage')}">参数配置</a></dd>
                    </dl>
                </li>
                <li data-name="Storage" class="layui-nav-item">
                    <a layui-href="javascript:;" lay-tips="订单" lay-direction="2">
                        <i class="layui-icon iconfont icon-caiwu1"></i>
                        <cite>财务</cite>
                    </a>
                    <dl class="layui-nav-child">
                        <dd><a layui-href="{:url('Finance/index')}">原始订单列表</a></dd>
                        <dd><a layui-href="{:url('Finance/outbound')}">出库明细列表</a></dd>
                    </dl>
                </li>
                <li data-name="Storage" class="layui-nav-item">
                    <a layui-href="javascript:;" lay-tips="仓库" lay-direction="2">
                        <i class="layui-icon iconfont icon-chanpin"></i>
                        <cite>基础</cite>
                    </a>
                    <dl class="layui-nav-child">
                        <dd><a layui-href="{:url('Storage/index')}">仓库</a></dd>
                        <dd><a layui-href="{:url('StorageArea/index')}">子仓库</a></dd>
                        <dd><a layui-href="{:url('StorageZone/index')}">邮编区块</a></dd>
                        <dd><a layui-href="{:url('StorageOutbound/index')}">出库费</a></dd>
                        <dd><a layui-href="{:url('StorageBase/index')}">基础费</a></dd>
                        <dd><a layui-href="{:url('StorageAhs/index')}">AHS</a></dd>
                        <dd><a layui-href="{:url('StorageDas/index')}">DAS</a></dd>
                        <dd><a layui-href="{:url('Product/index')}">产品</a></dd>
                    </dl>
                </li>
                <li data-name="Site" class="layui-nav-item">
                    <a layui-href="javascript:;" lay-tips="设置" lay-direction="2">
                        <i class="layui-icon layui-icon-set"></i>
                        <cite>设置</cite>
                    </a>
                    <dl class="layui-nav-child">
                        <dd><a layui-href="{:url('Param/web')}">参数设置</a></dd>
                        <!-- <dd><a layui-href="{:url('Mail/index')}">邮件设置</a></dd> -->
                        {if condition="$user.super eq 1"}
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
                        {/if}
                    </dl>
                </li>
            </ul>
        </div>
    </div>
    <script type="text/javascript">
    layui.use(['jquery'], function(){
        var $ = layui.jquery;

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
