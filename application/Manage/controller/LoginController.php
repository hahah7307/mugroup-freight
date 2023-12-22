<?php
namespace app\Manage\controller;

use app\Manage\model\AccountModel;
use app\Manage\model\AdminLoginModel;
use app\Manage\validate\AdminLoginValidate;
use think\Controller;
use think\Session;
use think\Config;
use think\Db;

class LoginController extends Controller
{
    public function index()
    {
        if ($this->request->isPost()) {
            $post = $this->request->post();
            // 验证账号
            if (empty($post['username'])) {
                return json(['code' => 0, 'msg' => '请输入账号！']);
            }
            // 验证密码
            if (empty($post['password'])) {
                return json(['code' => 0, 'msg' => '请输入密码！']);
            }
            // 验证码
            if ($post['vercode']) {
                if ($post['vercode'] != session::get('captch_code')) {
                    return json(['code' => 0, 'msg' => '验证码错误！']);
                }
            } else {
                return json(['code' => 0, 'msg' => '请输入验证码！']);
            }

            $account = Db::name('admin_user')->where(['username' => $post['username'], 'status' => AccountModel::STATUS_ACTIVE])->find();
            if ($account) {
                if (encPass($post['password'], $account['password_hash']) == $account['password']) {
                    Session::set(Config::get('USER_LOGIN_FLAG'), $account['id']);
                    Session::set(Config::get('USER_LOGIN_TIME'), time());

                    // 获取权限列表
                    $access = AccountModel::account_access($account['id']);
                    Session::set('access', $access, 'access');

                    // 登录记录
                    $dataLogin = [
                        'aid'       =>  $account['id'],
                        'login_ip'  =>  ip2long($this->request->ip()),
                    ];
                    $dataValidate = new AdminLoginValidate();
                    if ($dataValidate->scene('add')->check($dataLogin)) {
                        $model = new AdminLoginModel();
                        if ($model->allowField(true)->save($dataLogin)) {
                            return json(['code' => 1, 'msg' => '登录成功！']);
                        } else {
                            return json(['code' => 0, 'msg' => '登录失败！']);
                        }
                    } else {
                        return json(['code' => 0, 'msg' => '登录失败！']);
                    }
                } else {
                    return json(['code' => 0, 'msg' => '账号或密码有误！']);
                }
            } else {
                return json(['code' => 0, 'msg' => '账号或密码有误！']);
            }
        } else {

            return view();
        }
    }

    public function forget()
    {
        if ($this->request->isPost()) {
            $post = $this->request->post();
            // 手机号规则
            if (empty($post['cellphone']) || !preg_match('/^[1](([3][0-9])|([4][5-9])|([5][0-3,5-9])|([6][5,6])|([7][0-8])|([8][0-9])|([9][1,8,9]))[0-9]{8}$$/u',$post['cellphone'])) {
                return json(['code' => 0, 'msg' => '请输入手机号码！']);
            }
            // 短信验证码是否填写
            if (empty($post['vercode'])) {
                return json(['code' => 0, 'msg' => '请输入短信验证码！']);
            }
            // 密码是否填写
            if (empty($post['password'])) {
                return json(['code' => 0, 'msg' => '请输入新密码！']);
            }

            $account = Db::name('admin_user')->where(['phone' => $post['cellphone'], 'status' => AccountModel::STATUS_ACTIVE])->find();
            // 验证手机号
            if (empty($account)) {
                return json(['code' => 0, 'msg' => '手机号未被注册！']);
            }
            // 验证短信验证码
            //
            //
            
            // 验证密码
            if ($post['repass'] && $post['password'] == $post['repass']) {
                $user = AccountModel::get($account['id']);
                $user->password = encPass($post['password'], $account['password_salt']);
                if ($user->save()) {
                    return json(['code' => 1, 'msg' => '重置密码成功！']);
                } else {
                    return json(['code' => 0, 'msg' => '重置密码失败，请重试！']);
                }
            } else {
                return json(['code' => 0, 'msg' => '两次密码输入不一致！']);
            }
        } else {
            return view();
        }
    }

    public function logout()
    {
        Session::delete(Config::get('USER_LOGIN_FLAG'));
        Session::delete(Config::get('USER_LOGIN_TIME'));
        Session::delete(Config::get('USER_MENU'));
        $this->redirect('Login/index');
    }

    public function captcha()
    {
        //必须至于顶部,多服务器端记录验证码信息，便于用户输入后做校验
        session_start();

        //默认返回的是黑色的照片
        $image = imagecreatetruecolor(100, 30);
        //将背景设置为白色的
        $bgcolor = imagecolorallocate($image, 255, 255, 255);
        //将白色铺满地图
        imagefill($image, 0, 0, $bgcolor);

        //空字符串，每循环一次，追加到字符串后面  
        $captch_code = '';

        //验证码为随机四个数字
        for ($i = 0; $i < 4; $i ++) { 
            $fontsize = 20;
            $fontcolor = imagecolorallocate($image,rand(0,120),rand(0,120),rand(0,120));

            //产生随机数字0-9
            $fontcontent = rand(0,9);
            $captch_code .= $fontcontent;
            //数字的位置，0，0是左上角。不能重合显示不完全
            $x = ($i * 100 / 4) + rand(5,10);
            $y = rand(5,10);
            imagestring($image,$fontsize,$x,$y,$fontcontent,$fontcolor);
        }

        $_SESSION['captch_code'] = $captch_code;
        //为验证码增加干扰元素，控制好颜色，
        //点   
        for ($i = 0; $i < 200; $i ++) { 
            $pointcolor = imagecolorallocate($image,rand(50,200),rand(50,200),rand(50,200));
            imagesetpixel($image, rand(1,99), rand(1,29), $pointcolor);
        }

        //为验证码增加干扰元素
        //线   
        for ($i = 0; $i < 3; $i ++) { 
            $linecolor = imagecolorallocate($image,rand(80,220),rand(80,220),rand(80,220));
            imageline($image, rand(1,99), rand(1,29),rand(1,99), rand(1,29) ,$linecolor);
        }

        header('content-type:image/png');
        imagepng($image);

        //销毁
        imagedestroy($image);
    }
}
