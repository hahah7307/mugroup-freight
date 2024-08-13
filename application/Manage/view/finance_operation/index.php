
{include file="public/header" /}

<!-- 主体内容 -->
<div class="layui-body" id="LAY_app_body">
    <div class="right">
        <div class="title">营运费用</div>
        <div class="layui-form">
            <a href="{:url('expenses')}" class="layui-btn">国内广告</a>
            <a href="{:url('factory')}" class="layui-btn">工厂费用</a>
            <a href="{:url('delivery')}" class="layui-btn">国际快递</a>
        </div>
    </div>
</div>
<script>
    layui.use(['form', 'jquery'], function(){
        let $ = layui.jquery,
            form = layui.form;

    });
</script>

{include file="public/footer" /}
