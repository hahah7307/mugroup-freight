
{include file="public/header" /}
<style>
    .right {min-width: 480px; width: 480px}
    #USAMap {z-index: 99999999}
</style>
<script src="/static/echarts/dist/echarts.min.js"></script>
<script src="/static/echarts/test/lib/jquery.min.js"></script>
<div class="layui-body" id="LAY_app_body">
    <div class="right" style="width: 480px">
        <div class="title">美国各州销量热力图</div>
        <form class="layui-form" method="get">
            <div class="layui-form-item">
                <label class="layui-form-label">开始时间</label>
                <div class="layui-input-inline w300">
                    <input type="text" class="layui-input" id="sale_start" name="sale_start" value="{$sale_start}" placeholder="开始时间">
                </div>
            </div>
            <div class="layui-form-item">
                <label class="layui-form-label">结束时间</label>
                <div class="layui-input-inline w300">
                    <input type="text" class="layui-input" id="sale_end" name="sale_end" value="{$sale_end}" placeholder="结束时间">
                </div>
            </div>
            <div class="layui-form-item">
                <label class="layui-form-label">参考数量</label>
                <div class="layui-input-inline w300">
                    <input type="text" class="layui-input" name="quantity" value="{$quantity}" placeholder="请填写参考数量">
                </div>
            </div>
            {foreach name="sku" item="v" key="k"}
            <div class="layui-form-item">
                <label class="layui-form-label">{if condition="$k eq 0"}仓库SKU{/if}</label>
                <div class="layui-input-inline w300">
                    <input type="text" class="layui-input" name="sku[]" value="{$v}" placeholder="请填写仓库SKU">
                </div>
                {if condition="$k eq 0"}
                <button class="layui-btn layui-btn-sm btn-lc" lay-submit lay-filter="AttrAdd">添加</button>
                {else/}
                <button class="layui-btn layui-btn-sm layui-btn-danger btn-lc" lay-submit lay-filter="attrDel">删除</button>
                {/if}
            </div>
            {/foreach}
            <div class="layui-form-item" id="sub-dom">
                <div class="layui-input-block">
                    <button class="layui-btn w200" lay-submit lay-filter="Search"><i class="layui-icon">&#xe615;</i> 查询</button>
                </div>
            </div>
        </form>
    </div>
</div>
<div id="USAMap" style="width: 1800px;height:1200px;margin-left: 720px"></div>

<script>
    layui.use(['form', 'jquery', 'laydate'], function(){
        let $ = layui.jquery,
            form = layui.form,
            laydate = layui.laydate;

        // 显示日期选择器
        laydate.render({
            elem: '#sale_start',
            type: 'date'
        });
        laydate.render({
            elem: '#sale_end',
            type: 'date'
        });

        let domIndex = 0;
        // 添加属性
        form.on('submit(AttrAdd)', function(data) {
            domIndex ++;
            let newDom = '<div class="layui-form-item"><label class="layui-form-label"></label><div class="layui-input-inline w300"><input type="text" class="layui-input" name="sku[' + domIndex + ']" placeholder="请填写仓库SKU"></div><button class="layui-btn layui-btn-sm layui-btn-danger btn-lc" lay-submit lay-filter="attrDel">删除</button></div>';
            $("#sub-dom").before(newDom);
            form.render();
            return false;
        });

        // 删除属性
        form.on('submit(attrDel)', function(data) {
            $(this).parent().remove();
        });
    });
</script>
<script>
    // 美国地图
    USAMap();

    function USAMap() {
        let myChart = echarts.init(document.getElementById("USAMap"));
        // 开启加载loading的动画
        myChart.showLoading();
        // jquery读取json文件
        $.get('data/data.json', function (usaJson) {
            // 隐藏loading的动画
            myChart.hideLoading();
            echarts.registerMap('USA', usaJson, {
                // 把阿拉斯加移到美国主大陆左下方
                Alaska: {
                    left: -131,
                    top: 25,
                    width: 15
                },
                // 夏威夷
                Hawaii: {
                    left: -110,
                    top: 28,
                    width: 5
                },
                // 波多黎各（因为名字有空格，所以写为字符串的形式）
                'Puerto Rico': {
                    left: -76,
                    top: 26,
                    width: 2
                }
            });
            option = {
                // title: {
                //     text: 'USA Population Estimates (2012)',
                //     subtext: 'Data from www.census.gov',
                //     sublink: 'http://www.census.gov/popest/data/datasets.html',
                //     left: 'right'
                // },
                // 提示框组件
                tooltip: {
                    trigger: 'item',
                    // 浮层显示的延迟
                    showDelay: 0,
                    // 提示框浮层的移动动画过渡时间
                    transitionDuration: 0.2,
                    // 按要求的格式显示提示框
                    formatter: function (params) {
                        var value = (params.value + '').split('.');
                        value = value[0].replace(/(\d{1,3})(?=(?:\d{3})+(?!\d))/g, '$1,');
                        return params.seriesName + '<br/>' + params.name + ': ' + value;
                    }
                },
                // 可视映射
                visualMap: {
                    left: 'right',
                    min: 0,
                    max: {$quantity},
                    // 颜色区间
                    inRange: {
                        color: ['#313695', '#4575b4', '#74add1', '#abd9e9', '#e0f3f8', '#ffffbf', '#fee090', '#fdae61', '#f46d43', '#d73027', '#a50026']
                    },
                    // 文本，默认为数值文本
                    text: ['最高销售量', '最低销售量'],
                    // 显示拖拽用的手柄
                    calculable: true
                },
                // 工具盒
                toolbox: {
                    show: true,
                    //orient: 'vertical',
                    left: 'left',
                    top: 'top',
                    feature: {
                        // 数据视图
                        // dataView: {readOnly: false},
                        // 还原
                        restore: {},
                        // 保存为图片
                        // saveAsImage: {}
                    }
                },
                series: [
                    {
                        name: '销售数量',
                        type: 'map',
                        // 开启鼠标缩放和平移漫游
                        roam: true,
                        map: 'USA',
                        // 显示标签
                        emphasis: {
                            label: {
                                show: true
                            }
                        },
                        // 文本位置修正
                        textFixed: {
                            Alaska: [20, -20]
                        },
                        itemStyle: {
                            normal: {
                                label: {
                                    color: 'black',
                                    fontsize: 12,
                                    show: true,
                                    position: 'inner',
                                    formatter: '{b}'
                                }
                            }
                        },
                        data: {$list}
                    }
                ]
            };

            myChart.setOption(option);
        });
    }


</script>

{include file="public/footer" /}