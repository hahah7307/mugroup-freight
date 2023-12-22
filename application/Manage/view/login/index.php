<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>登录</title>
    <meta name="renderer" content="webkit">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=0">
    <link rel="stylesheet" href="/static/layuiadmin/layui/css/layui.css" media="all">
    <link rel="stylesheet" href="/static/layuiadmin/style/admin.css" media="all">
    <link rel="stylesheet" href="/static/layuiadmin/style/login.css" media="all">
    <link rel="stylesheet" href="/static/manage/css/manage.css" media="all">
</head>
<body>

    <div class="layadmin-user-login layadmin-user-display-show" id="LAY-user-login" style="display: none;">

        <div class="layadmin-user-login-main">
            <div class="layadmin-user-login-box layadmin-user-login-header">
                <h2>后台登录</h2>
            </div>
            <div class="layadmin-user-login-box layadmin-user-login-body layui-form">
                <div class="layui-form-item">
                    <label class="layadmin-user-login-icon layui-icon layui-icon-username" for="LAY-user-login-username"></label>
                    <input type="text" name="username" id="LAY-user-login-username" lay-verify="required" placeholder="用户名" class="layui-input">
                </div>
                <div class="layui-form-item">
                    <label class="layadmin-user-login-icon layui-icon layui-icon-password" for="LAY-user-login-password"></label>
                    <input type="password" name="password" id="LAY-user-login-password" lay-verify="required" placeholder="密码" class="layui-input">
                </div>
                <div class="layui-form-item">
                    <div class="layui-row">
                        <div class="layui-col-xs7">
                            <label class="layadmin-user-login-icon layui-icon layui-icon-vercode" for="LAY-user-login-vercode"></label>
                            <input type="text" name="vercode" id="LAY-user-login-vercode" lay-verify="required" placeholder="图形验证码" class="layui-input">
                        </div>
                        <div class="layui-col-xs5">
                            <div style="margin-left: 10px;">
                                <img src="/Manage/Login/captcha.html" class="user-login-codeimg">
                            </div>
                        </div>
                    </div>
                </div>
<!--                <div class="layui-form-item" style="margin-bottom: 20px;">-->
<!--                    <a href="{:url('Login/forget')}" class="layadmin-user-jump-change layadmin-link" style="margin-top: 7px;">忘记密码？</a>-->
<!--                </div>-->
                <div class="layui-form-item">
                    <button class="layui-btn layui-btn-fluid" lay-submit lay-filter="user-login">登 录</button>
                </div>
            </div>
        </div>
    
        <div class="layui-trans layadmin-user-login-footer">
        </div>
    </div>
<script src="/static/layuiadmin/layui/layui.js"></script>
<script src="/static/js/axios.min.js"></script>
<script>
layui.config({
    base: '/static/layuiadmin/' //静态资源所在路径
}).extend({
    index: 'lib/index' //主入口模块
}).use(['index', 'user'], function(){
    var $ = layui.$
        ,setter = layui.setter
        ,admin = layui.admin
        ,form = layui.form
        ,router = layui.router()
        ,search = router.search;

    form.render();

    $(".user-login-codeimg").click(function(){
        $(this).attr('src', "/Manage/Login/captcha.html");
    });

    //提交
    form.on('submit(user-login)', function(obj){
        //请求登入接口
        axios.post("{:url('index')}", obj.field)
        .then(function (response) {
            var res = response.data;
            if (res.code === 1) {
                //登入成功的提示与跳转
                layer.msg(res.msg, {
                    offset: '15px'
                    ,icon: 1
                    ,time: 1000
                }, function(){
                    location.href = '../'; //后台主页
                });
            } else {
                layer.msg(res.msg, {
                    offset: '15px'
                    ,icon: 2
                    ,time: 1000
                }, function(){
                    $(".user-login-codeimg").click();
                });
            }
        })
        .catch(function (error) {
            console.log(error);
        });
    });
});
</script>
</body>
</html>